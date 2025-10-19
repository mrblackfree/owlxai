<?php

declare(strict_types=1);

namespace Ai\Domain\Slide;

use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Model;
use Generator;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

interface SlideServiceInterface extends AiServiceInterface
{
    /**
     * Generate a slide presentation
     *
     * @param WorkspaceEntity $workspace
     * @param UserEntity $user
     * @param Model $model
     * @param array<string,mixed>|null $params
     * @return Generator<int,Chunk,null,SlideResponse>
     */
    public function generateSlide(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): Generator;
}
