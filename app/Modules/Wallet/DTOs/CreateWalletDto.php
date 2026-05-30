<?php

declare(strict_types=1);

namespace Modules\Wallet\DTOs;

final class CreateWalletDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $currency = 'SYP',
        public readonly int $kycTierRequired = 1,
        public readonly int $dailyLimit = 5000000,
    ) {}
}
