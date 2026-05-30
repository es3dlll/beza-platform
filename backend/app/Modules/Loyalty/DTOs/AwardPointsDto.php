<?php

declare(strict_types=1);

namespace Modules\Loyalty\DTOs;

class AwardPointsDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly int $points = 0,
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId = null,
        public readonly ?string $description = null,
    ) {}
}
