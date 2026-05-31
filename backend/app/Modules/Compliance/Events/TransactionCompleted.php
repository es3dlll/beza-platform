<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class TransactionCompleted
{
    public function __construct(
        public string $transactionId,
        public string $accountId,
        public string $recipientId,
        public int $amount,
        public string $currency,
        public string $deviceFingerprint,
        public string $country,
        public int $dailyTransactionCount,
        public bool $isNewDevice,
        public bool $isUntrustedDevice,
        public bool $isNewRecipient,
        public int $recipientRepeatAmount,
        public int $timestamp,
    ) {}
}
