<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Core\Services\CacheOrchestrator;
use App\Modules\Wallet\Events\LimitApproached;
use App\Modules\Wallet\Events\LimitExceeded;
use App\Modules\Wallet\ValueObjects\WalletLimit;
use Illuminate\Support\Facades\Event;

final class DynamicLimitService
{
    private const CACHE_NAMESPACE = 'wallet_limits';
    private const CACHE_TTL = 300;

    private const TIER_LIMITS = [
        'T1' => ['daily' => 5000000, 'monthly' => 50000000, 'single' => 1000000],
        'T2' => ['daily' => 50000000, 'monthly' => 500000000, 'single' => 10000000],
        'T3' => ['daily' => 500000000, 'monthly' => 5000000000, 'single' => 100000000],
    ];

    private const TIER_THRESHOLD = 0.85;

    public function __construct(
        private readonly CacheOrchestrator $cache,
    ) {}

    public function getLimits(string $userId, string $tier): WalletLimit
    {
        $cached = $this->cache->get(self::CACHE_NAMESPACE, $userId);
        if ($cached instanceof WalletLimit) {
            return $cached;
        }

        $tierLimits = self::TIER_LIMITS[$tier] ?? self::TIER_LIMITS['T1'];

        $dailyUsed = $this->getDailyUsage($userId);
        $monthlyUsed = $this->getMonthlyUsage($userId);

        $limit = new WalletLimit(
            dailyMax: $tierLimits['daily'],
            monthlyMax: $tierLimits['monthly'],
            singleMax: $tierLimits['single'],
            dailyUsed: $dailyUsed,
            monthlyUsed: $monthlyUsed,
        );

        $this->checkApproachThreshold($userId, $limit);
        $this->cache->writeThrough(self::CACHE_NAMESPACE, $userId, $limit, self::CACHE_TTL);

        return $limit;
    }

    public function checkLimit(string $userId, string $tier, int $amount): bool
    {
        $limits = $this->getLimits($userId, $tier);

        if (!$limits->canProcess($amount)) {
            Event::dispatch(new LimitExceeded($userId, $amount, $limits));
            return false;
        }

        return true;
    }

    public function invalidateCache(string $userId): void
    {
        $this->cache->invalidate(self::CACHE_NAMESPACE, $userId);
    }

    private function checkApproachThreshold(string $userId, WalletLimit $limit): void
    {
        $dailyRatio = $limit->dailyRemaining() / max($limit->dailyMaxSyp(), 1);
        $monthlyRatio = $limit->monthlyRemaining() / max($limit->monthlyMaxSyp(), 1);

        if ($dailyRatio < self::TIER_THRESHOLD || $monthlyRatio < self::TIER_THRESHOLD) {
            Event::dispatch(new LimitApproached($userId, $limit));
        }
    }

    private function getDailyUsage(string $userId): int
    {
        $value = $this->cache->get(self::CACHE_NAMESPACE, "daily_{$userId}");
        return (int) ($value ?? 0);
    }

    private function getMonthlyUsage(string $userId): int
    {
        $value = $this->cache->get(self::CACHE_NAMESPACE, "monthly_{$userId}");
        return (int) ($value ?? 0);
    }
}
