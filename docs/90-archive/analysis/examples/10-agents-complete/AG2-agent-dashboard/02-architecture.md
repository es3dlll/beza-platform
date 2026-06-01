# 02 - العمارة الفنية (Architecture) - لوحة تحكم الوكيل

## نظرة عامة على تدفق لوحة التحكم

```
[Flutter/React App] → [API Gateway] → [DashboardController] → [DashboardService]
                                                    ↓
                                          [Stats Aggregation]
                                                    ↓
                                   ┌──────────────────────────────┐
                                   │   Redis Cache Layer          │
                                   │   - Daily Stats (TTL: 5min)  │
                                   │   - Real-time Balance        │
                                   │   - Transaction History      │
                                   └──────────────────────────────┘
                                                    ↓
                                   ┌──────────────────────────────┐
                                   │   Data Sources               │
                                   │   - agent_transactions       │
                                   │   - agent_wallets            │
                                   │   - agents table             │
                                   └──────────────────────────────┘
```

## هيكل Middleware للوحة التحكم

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAgent()) {
            return response()->json([
                'message' => 'يجب أن تكون وكيلاً مسجلاً للوصول إلى لوحة التحكم',
                'code' => 'NOT_AGENT',
            ], 403);
        }

        $agent = $user->agent;
        if ($agent->status !== 'active') {
            return response()->json([
                'message' => 'حساب الوكيل غير نشط. يرجى التواصل مع الدعم.',
                'code' => 'AGENT_NOT_ACTIVE',
                'status' => $agent->status,
            ], 403);
        }

        return $next($request);
    }
}
```

## خدمة تجميع الإحصائيات

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\AgentWallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatisticsService
{
    private const CACHE_TTL = 300; // 5 دقائق
    private const CACHE_PREFIX = 'agent_dashboard_';

    public function __construct(
        private DashboardCacheService $cacheService,
    ) {}

    /**
     * الحصول على إحصائيات لوحة التحكم الكاملة
     */
    public function getDashboardStats(Agent $agent, array $filters = []): array
    {
        $cacheKey = $this->buildCacheKey($agent->id, $filters);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($agent, $filters) {
            return $this->aggregateStats($agent, $filters);
        });
    }

    /**
     * تجميع الإحصائيات من مصادر البيانات
     */
    private function aggregateStats(Agent $agent, array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? now()->startOfDay();
        $dateTo = $filters['date_to'] ?? now()->endOfDay();

        $wallet = $agent->wallet;
        $transactions = AgentTransaction::where('agent_id', $agent->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        return [
            'balance' => [
                'current' => (float) ($wallet?->balance ?? 0),
                'pending' => (float) ($wallet?->pending_balance ?? 0),
                'currency' => 'SAR',
            ],
            'daily_stats' => [
                'total_cash_in' => (float) (clone $transactions)
                    ->where('type', 'cash_in')
                    ->sum('amount'),
                'total_cash_out' => (float) (clone $transactions)
                    ->where('type', 'cash_out')
                    ->sum('amount'),
                'transaction_count' => (clone $transactions)->count(),
                'total_commission' => (float) (clone $transactions)
                    ->sum('commission'),
            ],
            'aggregate_totals' => [
                'total_cash_in' => (float) ($wallet?->total_cash_in ?? 0),
                'total_cash_out' => (float) ($wallet?->total_cash_out ?? 0),
                'total_commission_earned' => (float) ($wallet?->total_commission_earned ?? 0),
            ],
            'daily_limit' => [
                'max' => (float) $agent->max_daily_limit,
                'used' => (float) $agent->current_daily_total,
                'remaining' => (float) ($agent->max_daily_limit - $agent->current_daily_total),
            ],
            'meta' => [
                'cached_at' => now()->toIso8601String(),
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ];
    }

    private function buildCacheKey(int $agentId, array $filters): string
    {
        $hash = md5(json_encode($filters));
        return self::CACHE_PREFIX . "{$agentId}_{$hash}";
    }
}
```

## خدمة التخزين المؤقت (Cache)

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardCacheService
{
    private const REAL_TIME_BALANCE_TTL = 60; // 1 دقيقة
    private const STATS_TTL = 300; // 5 دقائق
    private const HISTORY_TTL = 600; // 10 دقائق

    /**
     * استراتيجية التخزين المؤقت
     * - الرصيد الفوري: يُخزن لمدة دقيقة واحدة
     * - الإحصائيات اليومية: 5 دقائق
     * - سجل المعاملات: 10 دقائق
     */
    public function cacheRealTimeBalance(int $agentId, float $balance): void
    {
        Cache::put(
            "agent_balance_{$agentId}",
            $balance,
            self::REAL_TIME_BALANCE_TTL
        );
    }

    public function getRealTimeBalance(int $agentId): ?float
    {
        return Cache::get("agent_balance_{$agentId}");
    }

    public function invalidateAgentCache(int $agentId): void
    {
        $patterns = [
            "agent_dashboard_{$agentId}_*",
            "agent_balance_{$agentId}",
            "agent_transactions_{$agentId}_*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        Log::info('تم مسح ذاكرة التخزين المؤقت للوكيل', [
            'agent_id' => $agentId,
        ]);
    }
}
```

## طبقات العمارة

```
┌──────────────────────────────────────┐
│         Presentation Layer           │
│  DashboardController (API/JSON)      │
├──────────────────────────────────────┤
│         Service Layer                │
│  DashboardStatisticsService          │
│  AgentWalletService                  │
│  DashboardCacheService               │
├──────────────────────────────────────┤
│         Cache Layer                  │
│  Redis - TTL-based invalidation      │
├──────────────────────────────────────┤
│         Data Access Layer            │
│  AgentTransaction (Model)            │
│  AgentWallet (Model)                 │
│  Agent (Model)                       │
├──────────────────────────────────────┤
│         Database Layer               │
│  MySQL / PostgreSQL with indexes     │
└──────────────────────────────────────┘
```

## ملاحظات

- **استراتيجية التخزين المؤقت:** يتم تخزين الإحصائيات مؤقتاً لتقليل استعلامات قاعدة البيانات.
- **الرصيد الفوري:** يتم جلب الرصيد الحقيقي عند الطلب مع تخزين مؤقت قصير.
- **الإحصائيات التراكمية:** تُحسب مسبقاً وتُخزن في جدول agent_wallets.
- **الترقيم (Pagination):** جميع القوائم تدعم الترقيم لتجنب تحميل كميات كبيرة من البيانات.
