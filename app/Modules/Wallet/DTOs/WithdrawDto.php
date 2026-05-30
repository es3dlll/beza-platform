<?php

declare(strict_types=1);

namespace Modules\Wallet\DTOs;

final class WithdrawDto
{
    public function __construct(
        public readonly string $walletId,
        public readonly int $amount,
        public readonly string $currency = 'SYP',
        public readonly string $referenceType = 'withdrawal',
        public readonly string $referenceId = '',
        public readonly string $channel = 'api',
        public readonly string $description = '',
        public readonly bool $applyFee = true,
        public readonly ?array $metadata = [],
    ) {}
}
