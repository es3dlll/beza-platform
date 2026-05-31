<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Consumers;

use App\Modules\EventBus\Contracts\EventConsumer;
use App\Modules\EventBus\Models\EventDeliveryLog;
use App\Modules\Fraud\Models\VelocityCounter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class VelocityUpdateConsumer implements EventConsumer
{
    private const SYSTEM_RULE_ID = 'system_async_velocity';
    private const WINDOW_HOURS = 1;

    public function getName(): string
    {
        return 'velocity_update';
    }

    public function handle(string $eventType, array $payload, EventDeliveryLog $log): void
    {
        $walletId = $payload['fromWalletId'] ?? $payload['walletId'] ?? null;

        if ($walletId === null) {
            Log::warning('VelocityUpdateConsumer: no wallet_id in payload', ['event_type' => $eventType]);
            return;
        }

        $windowKey = 'vel:' . $walletId . ':' . self::SYSTEM_RULE_ID . ':' . date('YmdH');
        $windowStart = now()->subHours(self::WINDOW_HOURS);

        $counter = VelocityCounter::firstOrCreate(
            [
                'wallet_id' => $walletId,
                'rule_id' => self::SYSTEM_RULE_ID,
                'window_key' => $windowKey,
            ],
            [
                'window_start' => $windowStart,
                'window_end' => now(),
                'count' => 0,
            ],
        );

        $counter->incrementCount();

        Log::debug("VelocityUpdateConsumer: incremented velocity for wallet {$walletId}, now {$counter->count}");
    }
}
