<?php

declare(strict_types=1);

namespace Ai\Domain\Slide;

use Billing\Domain\ValueObjects\CreditCount;

class SlideResponse
{
    public function __construct(
        public readonly array $slides,
        public readonly CreditCount $cost,
        public readonly ?string $theme = null,
        public readonly ?string $template = null,
    ) {}
}
