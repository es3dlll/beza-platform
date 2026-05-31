<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Events;

final readonly class InitiateLedgerTransfer
{
    public function __construct(
        public string $remittanceId,
        public string $idempotencyKey,
        public string $senderId,
        public string $fromCurrency,
        public string $toCurrency,
        public int $sourceAmount,
        public int $destinationAmount,
        public int $feeAmount,
        public int $totalCharge,
    ) {}
}
