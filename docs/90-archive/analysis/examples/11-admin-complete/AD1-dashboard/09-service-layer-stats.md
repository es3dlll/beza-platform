# 09 - DashboardStatsService

```php
<?php
// app/Services/Admin/DashboardStatsService.php

namespace App\Services\Admin;

use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    private const CACHE_KEY = 'dashboard_stats';
    private const CACHE_TTL = 300; // 5 دقائق

    /**
     * الحصول على إحصائيات لوحة التحكم
     */
    public function getStats(string $period = '30d'): array
    {
        // محاولة قراءة من Cache
        $cached = $this->getFromCache();

        if ($cached) {
            return $cached;
        }

        // توليد البيانات
        $stats = $this->generateStats($period);

        // تخزين في Cache
        $this->storeInCache($stats);

        return $stats;
    }

    private function generateStats(string $period): array
    {
        $days = $this->parsePeriod($period);

        return [
            // إحصائيات عامة
            'total_users'           => $this->getTotalUsers(),
            'active_users'          => $this->getActiveUsers(),
            'daily_active_users'    => $this->getDailyActiveUsers($days),
            'total_transactions'    => $this->getTotalTransactions(),
            'transaction_volume'    => $this->getTransactionVolume(),
            'total_wallets_balance' => $this->getTotalWalletsBalance(),
            'merchants_count'       => $this->getMerchantsCount(),
            'agents_count'          => $this->getAgentsCount(),
            'total_fees'            => $this->getTotalFees(),

            // رسوم بيانية
            'revenue_chart'     => $this->getRevenueChart($days),
            'volume_chart'      => $this->getVolumeChart($days),
            'user_growth_chart' => $this->getUserGrowthChart($days),
            'daily_active_chart'=> $this->getDailyActiveChart($days),

            // جداول
            'top_merchants'     => $this->getTopMerchants(),
            'recent_activities' => $this->getRecentActivities(),

            // Meta
            'cached_at' => now()->toIso8601String(),
            'expires_in'=> self::CACHE_TTL,
        ];
    }

    private function getTotalUsers(): int
    {
        return User::whereNull('deleted_at')->count();
    }

    private function getActiveUsers(): int
    {
        return User::whereNull('deleted_at')
            ->where('status', 'active')
            ->whereDate('last_login_at', today())
            ->count();
    }

    private function getDailyActiveUsers(int $days): array
    {
        return User::whereNull('deleted_at')
            ->where('last_login_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(last_login_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getTotalTransactions(): int
    {
        return Transaction::where('status', 'completed')->count();
    }

    private function getTransactionVolume(): float
    {
        return (float) Transaction::where('status', 'completed')
            ->sum('amount');
    }

    private function getTotalWalletsBalance(): float
    {
        return (float) Wallet::where('is_active', true)
            ->sum('balance');
    }

    private function getMerchantsCount(): int
    {
        return Merchant::where('status', 'active')->count();
    }

    private function getAgentsCount(): int
    {
        return DB::table('agents')->where('status', 'active')->count();
    }

    private function getTotalFees(): float
    {
        return (float) Transaction::where('type', 'fee')
            ->where('status', 'completed')
            ->sum('amount');
    }

    private function getRevenueChart(int $days): array
    {
        return Transaction::where('type', 'fee')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as value'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getVolumeChart(int $days): array
    {
        return Transaction::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as value'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getUserGrowthChart(int $days): array
    {
        return User::whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as new_users'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getDailyActiveChart(int $days): array
    {
        return DB::table('daily_active_users_log')
            ->where('date', '>=', now()->subDays($days))
            ->select('date', 'active_count as count')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getTopMerchants(): array
    {
        return Merchant::with('user')
            ->where('status', 'active')
            ->orderByDesc('total_volume')
            ->limit(5)
            ->get(['id', 'user_id', 'business_name', 'total_transactions', 'total_volume'])
            ->toArray();
    }

    private function getRecentActivities(): array
    {
        return Transaction::with(['fromWallet.user', 'toWallet.user'])
            ->where('status', 'completed')
            ->latest()
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function parsePeriod(string $period): int
    {
        return match ($period) {
            '7d'  => 7,
            '90d' => 90,
            '1y'  => 365,
            default => 30,
        };
    }

    private function getFromCache(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);
        return is_array($cached) ? $cached : null;
    }

    private function storeInCache(array $data): void
    {
        Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
```
