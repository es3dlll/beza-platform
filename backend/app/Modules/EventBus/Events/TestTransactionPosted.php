<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Events;

use App\Modules\EventBus\Contracts\AsyncEvent;

final class TestTransactionPosted implements AsyncEvent
{
    public function __construct(
        private readonly string $transactionId,
        private readonly int $amount,
        private readonly string $fromWalletId,
        private readonly string $toWalletId,
        private readonly string $journalEntryId,
    ) {}

    public function getEventType(): string
    {
        return 'financial_core.transaction_posted';
    }

    public function getEventVersion(): string
    {
        return 'v1';
    }

    public function getPayload(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'amount' => $this->amount,
            'fromWalletId' => $this->fromWalletId,
            'toWalletId' => $this->toWalletId,
            'journalEntryId' => $this->journalEntryId,
        ];
    }

    public function getSource(): string
    {
        return 'financial_core';
    }
}
