<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\EnhancedSlide;

use Ai\Domain\Slide\SlideServiceInterface;
use Ai\Domain\Slide\SlideResponse;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\AbstractBaseService;
use Ai\Infrastructure\Services\CostCalculator;
use Ai\Infrastructure\Services\OpenAi\Client;
use Ai\Infrastructure\Services\OpenAi\StreamResponse;
use Ai\Infrastructure\Services\Tools\GoogleSearch;
use Billing\Domain\ValueObjects\CreditCount;
use Easy\Container\Attributes\Inject;
use Generator;
use Override;
use RuntimeException;
use Shared\Infrastructure\Services\ModelRegistry;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

/**
 * GenSpark 스타일의 고급 슬라이드 생성 서비스
 * 
 * 기능:
 * - 웹 검색 통합
 * - 실시간 데이터 수집
 * - 자동 레이아웃 최적화
 * - 이미지 추천 및 생성
 * - 차트 및 그래프 자동 생성
 * - 스피커 노트 자동 생성
 */
class AdvancedSlideService extends AbstractBaseService implements SlideServiceInterface
{
    private const ENHANCED_SYSTEM_PROMPT = <<<'PROMPT'
You are an expert presentation designer with deep knowledge in visual communication, data visualization, and storytelling.

Your task is to create a comprehensive, professional presentation that:
1. Researches the topic thoroughly using available web search
2. Structures information logically with a clear narrative flow
3. Uses data visualization where appropriate (charts, graphs, diagrams)
4. Includes relevant images and visual elements
5. Provides detailed speaker notes for each slide
6. Maintains visual hierarchy and design best practices

PRESENTATION REQUIREMENTS:
- Minimum 8 slides, maximum 20 slides
- Each slide should have a clear purpose
- Use the Rule of Three for bullet points
- Include title slide, content slides, and conclusion slide
- Speaker notes should be comprehensive and helpful

OUTPUT FORMAT (STRICT JSON):
{
  "slides": [
    {
      "type": "title",
      "title": "Main Title",
      "subtitle": "Optional subtitle",
      "layout": "title_slide",
      "speaker_notes": "Introduction and overview",
      "design_notes": "Suggested colors, images, or style"
    },
    {
      "type": "content",
      "title": "Slide Title",
      "content": [
        "Main point 1 - keep it concise",
        "Main point 2 - one idea per bullet",
        "Main point 3 - use active voice"
      ],
      "layout": "title_and_content",
      "visual_elements": [
        {
          "type": "image",
          "description": "Description for image generation",
          "placement": "right"
        }
      ],
      "speaker_notes": "Detailed explanation for the presenter",
      "transition_notes": "How this connects to next slide"
    },
    {
      "type": "data",
      "title": "Data-Driven Slide",
      "content": [
        "Key insight from data",
        "Supporting evidence",
        "Actionable conclusion"
      ],
      "layout": "title_chart_and_content",
      "chart": {
        "type": "bar|line|pie|scatter",
        "data": {
          "labels": ["Q1", "Q2", "Q3", "Q4"],
          "values": [100, 150, 120, 180]
        },
        "title": "Chart Title",
        "description": "What the data shows"
      },
      "speaker_notes": "How to explain the data",
      "key_takeaway": "Main message from this slide"
    },
    {
      "type": "quote",
      "title": "Expert Opinion",
      "quote": "Meaningful quote that adds authority",
      "author": "Author Name",
      "author_title": "Author credentials",
      "layout": "quote_slide",
      "speaker_notes": "Context and relevance of the quote"
    },
    {
      "type": "comparison",
      "title": "Comparison Slide",
      "left_side": {
        "title": "Option A",
        "points": ["Pro 1", "Pro 2", "Pro 3"]
      },
      "right_side": {
        "title": "Option B",
        "points": ["Pro 1", "Pro 2", "Pro 3"]
      },
      "layout": "two_column",
      "speaker_notes": "How to discuss the comparison"
    },
    {
      "type": "conclusion",
      "title": "Key Takeaways",
      "content": [
        "Main conclusion 1",
        "Main conclusion 2",
        "Main conclusion 3"
      ],
      "call_to_action": "What the audience should do next",
      "layout": "conclusion_slide",
      "speaker_notes": "How to close powerfully"
    }
  ],
  "metadata": {
    "theme": "professional|creative|minimal|dark|colorful",
    "template": "modern|classic|business|academic|startup",
    "color_scheme": ["#primary", "#secondary", "#accent"],
    "font_pairing": {
      "heading": "Font name",
      "body": "Font name"
    },
    "estimated_duration_minutes": 15,
    "target_audience": "Description of intended audience",
    "key_message": "The one thing audience should remember"
  }
}

IMPORTANT: Return ONLY valid JSON, no markdown code blocks or additional text.
PROMPT;

    public function __construct(
        private Client $client,
        private CostCalculator $calc,
        private ModelRegistry $registry,
        private GoogleSearch $searchTool,
        
        #[Inject('option.features.slides.enable_web_search')]
        private ?bool $enableWebSearch = true,
        
        #[Inject('option.features.slides.enable_auto_images')]
        private ?bool $enableAutoImages = true,
        
        #[Inject('option.features.slides.max_slides')]
        private ?int $maxSlides = 20,
    ) {
        parent::__construct($registry, 'openai', 'llm');
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
        $slideCount = min((int)($params['slide_count'] ?? 10), $this->maxSlides);
        $includeImages = $params['include_images'] ?? $this->enableAutoImages;
        $includePlaceholders = $params['include_data_placeholders'] ?? true;

        // Step 1: Web search for research (if enabled)
        $researchData = '';
        if ($this->enableWebSearch && $this->searchTool->isEnabled()) {
            yield new Chunk("🔍 Researching topic...\n");
            $researchData = $this->performWebResearch($workspace, $user, $prompt);
        }

        // Step 2: Generate comprehensive slide structure
        $systemPrompt = $this->buildSystemPrompt(
            $prompt,
            $theme,
            $template,
            $slideCount,
            $researchData,
            $includeImages,
            $includePlaceholders
        );

        yield new Chunk("🎨 Designing presentation structure...\n");

        $resp = $this->client->sendRequest('POST', '/v1/chat/completions', [
            'model' => $model->value,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => self::ENHANCED_SYSTEM_PROMPT
                ],
                [
                    'role' => 'user',
                    'content' => $systemPrompt
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 4000,
            'response_format' => ['type' => 'json_object'],
            'stream' => true,
            'stream_options' => [
                'include_usage' => true
            ]
        ]);

        $inputTokensCount = 0;
        $outputTokensCount = 0;
        $content = '';
        $chunkBuffer = '';

        $stream = new StreamResponse($resp);
        foreach ($stream as $item) {
            if (isset($item->usage)) {
                $inputTokensCount += $item->usage->prompt_tokens ?? 0;
                $outputTokensCount += $item->usage->completion_tokens ?? 0;
            }

            $choice = $item->choices[0] ?? null;
            if (!$choice) {
                continue;
            }

            if (isset($choice->delta->content)) {
                $chunkContent = $choice->delta->content;
                $content .= $chunkContent;
                $chunkBuffer .= $chunkContent;
                
                // Stream progress updates
                if (strlen($chunkBuffer) > 50 || strpos($chunkBuffer, '}') !== false) {
                    yield new Chunk($chunkBuffer);
                    $chunkBuffer = '';
                }
            }
        }

        // Parse and validate JSON response
        try {
            $slideData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Failed to parse slide data: ' . $e->getMessage());
        }

        if (!isset($slideData['slides']) || !is_array($slideData['slides'])) {
            throw new RuntimeException('Invalid slide data structure');
        }

        // Process and enhance slides
        $slides = $this->processSlides($slideData['slides']);
        $metadata = $slideData['metadata'] ?? [];

        // Calculate costs
        $searchCost = $researchData ? new CreditCount(1) : new CreditCount(0);
        $generationCost = $this->calc->calculate($inputTokensCount, $model, $outputTokensCount);
        $totalCost = new CreditCount($searchCost->value + $generationCost->value);

        yield new Chunk("\n✅ Presentation created successfully!\n");

        return new SlideResponse(
            slides: $slides,
            cost: $totalCost,
            theme: $metadata['theme'] ?? $theme,
            template: $metadata['template'] ?? $template
        );
    }

    private function performWebResearch(
        WorkspaceEntity $workspace,
        UserEntity $user,
        string $prompt
    ): string {
        try {
            $searchResult = $this->searchTool->call(
                $user,
                $workspace,
                [
                    'query' => $prompt,
                    'hl' => 'en',
                    'gl' => 'us'
                ]
            );

            $searchData = json_decode($searchResult->content, true);
            
            // Extract relevant information
            $research = "RESEARCH FINDINGS:\n\n";
            
            if (isset($searchData['organic']) && is_array($searchData['organic'])) {
                foreach (array_slice($searchData['organic'], 0, 5) as $result) {
                    $research .= "- " . ($result['title'] ?? '') . "\n";
                    $research .= "  " . ($result['snippet'] ?? '') . "\n\n";
                }
            }

            if (isset($searchData['knowledgeGraph'])) {
                $kg = $searchData['knowledgeGraph'];
                $research .= "KEY FACTS:\n";
                $research .= "- " . ($kg['description'] ?? '') . "\n";
                if (isset($kg['attributes']) && is_array($kg['attributes'])) {
                    foreach ($kg['attributes'] as $key => $value) {
                        $research .= "- $key: $value\n";
                    }
                }
            }

            return $research;
        } catch (\Exception $e) {
            // Fail gracefully if search is unavailable
            return '';
        }
    }

    private function buildSystemPrompt(
        string $prompt,
        string $theme,
        string $template,
        int $slideCount,
        string $researchData,
        bool $includeImages,
        bool $includePlaceholders
    ): string {
        $systemPrompt = "Create a comprehensive presentation on: $prompt\n\n";
        $systemPrompt .= "Number of slides: $slideCount\n";
        $systemPrompt .= "Theme: $theme\n";
        $systemPrompt .= "Template style: $template\n\n";

        if ($researchData) {
            $systemPrompt .= $researchData . "\n\n";
        }

        if ($includeImages) {
            $systemPrompt .= "Include image suggestions with detailed descriptions for AI generation.\n";
        }

        if ($includePlaceholders) {
            $systemPrompt .= "Include data visualization placeholders where statistics would enhance the message.\n";
        }

        $systemPrompt .= "\nCreate a professional, engaging presentation that tells a compelling story.";

        return $systemPrompt;
    }

    private function processSlides(array $slides): array {
        $processed = [];
        
        foreach ($slides as $index => $slide) {
            $processed[] = [
                'id' => $index,
                'type' => $slide['type'] ?? 'content',
                'title' => $slide['title'] ?? 'Untitled Slide',
                'subtitle' => $slide['subtitle'] ?? null,
                'content' => $slide['content'] ?? [],
                'quote' => $slide['quote'] ?? null,
                'author' => $slide['author'] ?? null,
                'layout' => $slide['layout'] ?? 'title_and_content',
                'visual_elements' => $slide['visual_elements'] ?? [],
                'chart' => $slide['chart'] ?? null,
                'speaker_notes' => $slide['speaker_notes'] ?? '',
                'transition_notes' => $slide['transition_notes'] ?? '',
                'design_notes' => $slide['design_notes'] ?? '',
                'call_to_action' => $slide['call_to_action'] ?? null,
                'left_side' => $slide['left_side'] ?? null,
                'right_side' => $slide['right_side'] ?? null,
                'key_takeaway' => $slide['key_takeaway'] ?? null,
            ];
        }

        return $processed;
    }
}



