<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandBase;
use Ai\Application\CommandHandlers\GenerateSlideCommandHandler;
use Ai\Domain\ValueObjects\Model;
use Preset\Domain\Entities\PresetEntity;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\OverrideHandler;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[OverrideHandler(GenerateSlideCommandHandler::class)]
class GenerateSlideCommand extends CommandBase
{
    public string|Id|PresetEntity $prompt = '';
    public array $params = [];
    public ?string $theme = null;
    public ?string $template = null;

    public function __construct(
        public readonly WorkspaceEntity|Id $workspace,
        public readonly UserEntity|Id $user,
        public readonly Model $model
    ) {
    }

    public function setPrompt(string|Id|PresetEntity $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function setTheme(?string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function setTemplate(?string $template): self
    {
        $this->template = $template;
        return $this;
    }
}
