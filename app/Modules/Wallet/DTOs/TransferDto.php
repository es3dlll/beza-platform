<?php

declare(strict_types=1);

namespace Modules\Wallet\DTOs;

final class TransferDto
{
    public function __construct(
        public readonly string $fromWalletId,
        public readonly string $toWalletId,
        public readonly int $amount,
        public readonly string $currency = 'SYP',
        public readonly string $referenceId = '',
        public readonly string $channel = 'api',
        public readonly string $description = '',
        public readonly bool $applyFee = true,
        public readonly ?array $metadata = [],
    ) {}
}
