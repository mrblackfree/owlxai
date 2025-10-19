<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\SlideEntity;
use JsonSerializable;

class SlideResource implements JsonSerializable
{
    public function __construct(
        private SlideEntity $entity
    ) {}

    public function jsonSerialize(): array
    {
        $output = [
            'id' => $this->entity->getId(),
            'title' => $this->entity->getTitle(),
            'model' => $this->entity->getModel(),
            'slides' => $this->entity->getSlides(),
            'theme' => $this->entity->getTheme(),
            'template' => $this->entity->getTemplate(),
            'slide_count' => $this->entity->getSlideCount(),
            'cost' => $this->entity->getCost(),
            'created_at' => $this->entity->getCreatedAt()->getTimestamp(),
            'updated_at' => $this->entity->getUpdatedAt()->getTimestamp(),
        ];

        if ($this->entity->getPreset()) {
            $output['preset'] = new PresetResource($this->entity->getPreset());
        }

        return $output;
    }
}
