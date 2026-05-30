<?php

declare(strict_types=1);

namespace Modules\Float\DTOs;

final class CreateFloatAccountDto
{
    public function __construct(
        public readonly string $ownerType,
        public readonly string $ownerId,
        public readonly string $floatType,
        public readonly string $currency = 'SYP',
        public readonly int $minimumBalance = 0,
        public readonly ?int $maximumBalance = null,
    ) {}
}
