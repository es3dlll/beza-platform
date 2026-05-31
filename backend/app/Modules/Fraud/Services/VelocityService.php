<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Services;

use App\Modules\Fraud\Models\FraudRule;
use App\Modules\Fraud\Models\VelocityCounter;
use Illuminate\Support\Facades\Cache;

final class VelocityService
{
    public function checkAndIncrement(string $walletId, FraudRule $rule): bool
    {
        $windowMinutes = $rule->time_window_minutes ?? 60;
        $windowKey = $this->windowKey($walletId, $rule->id, $windowMinutes);

        $count = $this->incrementRedis($windowKey, $windowMinutes);
        $this->incrementDb($walletId, $rule, $windowKey, $windowMinutes);

        return $count <= $rule->threshold;
    }

    public function getCount(string $walletId, FraudRule $rule): int
    {
        $windowMinutes = $rule->time_window_minutes ?? 60;
        $windowKey = $this->windowKey($walletId, $rule->id, $windowMinutes);

        return Cache::get($windowKey, 0);
    }

    private function incrementRedis(string $windowKey, int $ttlMinutes): int
    {
        $count = Cache::get($windowKey, 0);
        $count++;

        Cache::put($windowKey, $count, now()->addMinutes($ttlMinutes));

        return $count;
    }

    private function incrementDb(string $walletId, FraudRule $rule, string $windowKey, int $windowMinutes): void
    {
        $windowStart = now()->subMinutes($windowMinutes);

        $counter = VelocityCounter::firstOrCreate(
            ['wallet_id' => $walletId, 'rule_id' => $rule->id, 'window_key' => $windowKey],
            ['window_start' => $windowStart, 'window_end' => now(), 'count' => 0],
        );

        $counter->incrementCount();
    }

    private function windowKey(string $walletId, string $ruleId, int $windowMinutes): string
    {
        $period = now()->subMinutes($windowMinutes)->format('YmdHi');
        $roundedPeriod = substr($period, 0, strlen($period) - 1) . '0';
        return "vel:{$walletId}:{$ruleId}:{$roundedPeriod}";
    }
}
