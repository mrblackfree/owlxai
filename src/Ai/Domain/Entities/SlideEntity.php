<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\RequestParams;
use Ai\Domain\ValueObjects\State;
use Ai\Domain\ValueObjects\Title;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\CreditCount;
use Doctrine\ORM\Mapping as ORM;
use Preset\Domain\Entities\PresetEntity;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[ORM\Entity]
class SlideEntity extends AbstractLibraryItemEntity
{

    #[ORM\ManyToOne(targetEntity: PresetEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?PresetEntity $preset = null;

    public function __construct(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        Title $title,
        ?PresetEntity $preset = null,
        ?RequestParams $requestParams = null,
        ?CreditCount $cost = null,
        ?Visibility $visibility = null,
    ) {
        parent::__construct(
            $workspace,
            $user,
            $model,
            $title,
            $requestParams,
            $cost,
            $visibility
        );

        $this->preset = $preset;
        $this->state = State::COMPLETED;
    }

    public function getSlides(): array
    {
        return $this->getMeta('slides') ?? [];
    }

    public function setSlides(array $slides): self
    {
        $this->addMeta('slides', $slides);
        return $this;
    }

    public function addSlide(array $slide): self
    {
        $slides = $this->getSlides();
        $slides[] = $slide;
        $this->setSlides($slides);
        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->getMeta('theme');
    }

    public function setTheme(?string $theme): self
    {
        $this->addMeta('theme', $theme);
        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->getMeta('template');
    }

    public function setTemplate(?string $template): self
    {
        $this->addMeta('template', $template);
        return $this;
    }

    public function getPreset(): ?PresetEntity
    {
        return $this->preset;
    }

    public function getSlideCount(): int
    {
        return count($this->slides);
    }

    public function getSlideByIndex(int $index): ?array
    {
        return $this->slides[$index] ?? null;
    }

    public function updateSlide(int $index, array $slide): self
    {
        if (isset($this->slides[$index])) {
            $this->slides[$index] = $slide;
        }
        return $this;
    }

    public function removeSlide(int $index): self
    {
        if (isset($this->slides[$index])) {
            array_splice($this->slides, $index, 1);
        }
        return $this;
    }
}
