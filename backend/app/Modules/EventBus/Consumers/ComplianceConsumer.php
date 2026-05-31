<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Consumers;

use App\Modules\EventBus\Contracts\EventConsumer;
use App\Modules\EventBus\Models\EventDeliveryLog;
use App\Modules\Compliance\Events\TransactionCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

final class ComplianceConsumer implements EventConsumer
{
    public function getName(): string
    {
        return 'compliance_monitor';
    }

    public function handle(string $eventType, array $payload, EventDeliveryLog $log): void
    {
        Log::info('ComplianceConsumer: processing event', [
            'event_type' => $eventType,
        ]);

        $completedEvent = new TransactionCompleted(
            transactionId: $payload['transactionId'] ?? $payload['id'] ?? 'unknown',
            accountId: $payload['fromWalletId'] ?? $payload['accountId'] ?? 'unknown',
            recipientId: $payload['toWalletId'] ?? $payload['recipientId'] ?? 'unknown',
            amount: (int) ($payload['amount'] ?? 0),
            currency: $payload['currency'] ?? 'SYP',
            deviceFingerprint: $payload['deviceFingerprint'] ?? 'unknown',
            country: $payload['country'] ?? 'SY',
            dailyTransactionCount: (int) ($payload['dailyTransactionCount'] ?? 0),
            isNewDevice: (bool) ($payload['isNewDevice'] ?? false),
            isUntrustedDevice: (bool) ($payload['isUntrustedDevice'] ?? false),
            isNewRecipient: (bool) ($payload['isNewRecipient'] ?? false),
            recipientRepeatAmount: (int) ($payload['recipientRepeatAmount'] ?? 0),
            timestamp: now()->getTimestamp(),
        );

        Event::dispatch($completedEvent);
    }
}
