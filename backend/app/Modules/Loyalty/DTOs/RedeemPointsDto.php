<?php

declare(strict_types=1);

namespace Modules\Loyalty\DTOs;

class RedeemPointsDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly int $points = 0,
        public readonly ?string $rewardId = null,
        public readonly ?string $description = null,
    ) {}
}
