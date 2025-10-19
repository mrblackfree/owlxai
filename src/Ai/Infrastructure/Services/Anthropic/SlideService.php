<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Anthropic;

use Ai\Domain\Slide\SlideServiceInterface;
use Ai\Domain\Slide\SlideResponse;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\AbstractBaseService;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Generator;
use Override;
use RuntimeException;
use Shared\Infrastructure\Services\ModelRegistry;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class SlideService extends AbstractBaseService implements SlideServiceInterface
{
    private const SLIDE_GENERATION_PROMPT = <<<'PROMPT'
    Create a professional presentation based on the following topic: {prompt}

    Requirements:
    - Generate a well-structured presentation with 8-12 slides
    - Each slide should have a title, content, and speaker notes
    - Content should be concise, using bullet points where appropriate
    - Include relevant data, statistics, or examples where applicable
    - Maintain a logical flow throughout the presentation

    Theme: {theme}
    Template: {template}

    Return the presentation in the following JSON format:
    {
        "slides": [
            {
                "title": "Slide Title",
                "content": ["Bullet point 1", "Bullet point 2", "Bullet point 3"],
                "speaker_notes": "Detailed speaker notes for this slide",
                "layout": "title_and_content"
            }
        ],
        "theme": "professional",
        "template": "modern"
    }

    Important: Return ONLY valid JSON, no additional text or markdown formatting.
    PROMPT;

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private ModelRegistry $registry,
    ) {
        parent::__construct($registry, 'anthropic', 'llm');
    }

    #[Override]
    public function generateSlide(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): Generator {
        $prompt = $params['prompt'] ?? '';
        $theme = $params['theme'] ?? 'professional';
        $template = $params['template'] ?? 'modern';

        $systemPrompt = str_replace(
            ['{prompt}', '{theme}', '{template}'],
            [$prompt, $theme, $template],
            self::SLIDE_GENERATION_PROMPT
        );

        $resp = $this->client->sendRequest('POST', '/v1/messages', [
            'model' => $model->value,
            'system' => 'You are a professional presentation designer. Create well-structured, engaging presentations and return JSON only.',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $systemPrompt
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 4096,
            'stream' => true
        ]);

        $inputTokensCount = 0;
        $outputTokensCount = 0;
        $content = '';

        $stream = new StreamResponse($resp);
        foreach ($stream as $item) {
            if ($item->type === 'message_start') {
                $inputTokensCount = $item->message->usage->input_tokens ?? 0;
            }

            if ($item->type === 'message_delta') {
                $outputTokensCount = $item->usage->output_tokens ?? 0;
            }

            if ($item->type === 'content_block_delta' && isset($item->delta->text)) {
                $content .= $item->delta->text;
                yield new Chunk($item->delta->text);
            }
        }

        // Parse the JSON response
        $slideData = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse slide generation response: ' . json_last_error_msg());
        }

        // Calculate cost
        if ($this->client->hasCustomKey()) {
            $cost = new CreditCount(0);
        } else {
            $inputCost = $this->calc->calculate(
                $inputTokensCount,
                $model,
                CostCalculator::INPUT
            );

            $outputCost = $this->calc->calculate(
                $outputTokensCount,
                $model,
                CostCalculator::OUTPUT
            );

            $cost = new CreditCount($inputCost->value + $outputCost->value);
        }

        return new SlideResponse(
            $slideData['slides'] ?? [],
            $cost,
            $slideData['theme'] ?? $theme,
            $slideData['template'] ?? $template
        );
    }
}
