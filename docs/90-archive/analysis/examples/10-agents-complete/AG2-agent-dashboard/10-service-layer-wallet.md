# 10 - طبقة خدمة المحفظة (Wallet Service) - لوحة تحكم الوكيل

## خدمة محفظة الوكيل الخاصة بلوحة التحكم

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentWallet;
use App\Models\AgentTransaction;
use App\Models\AgentDailySnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\DashboardDataUnavailableException;

class AgentDashboardWalletService
{
    public function __construct(
        private DashboardCacheService $cacheService,
    ) {}

    /**
     * الحصول على المجموع اليومي (Cash In - Cash Out)
     */
    public function getDailyTotals(Agent $agent, ?string $date = null): array
    {
        $date = $date ?: now()->toDateString();

        return Cache::remember(
            "agent_daily_totals_{$agent->id}_{$date}",
            300,
            function () use ($agent, $date) {
                $transactions = AgentTransaction::where('agent_id', $agent->id)
                    ->whereDate('created_at', $date);

                return [
                    'date' => $date,
                    'total_cash_in' => (float) (clone $transactions)
                        ->where('type', 'cash_in')
                        ->sum('amount'),
                    'total_cash_out' => (float) (clone $transactions)
                        ->where('type', 'cash_out')
                        ->sum('amount'),
                    'net' => 0, // تُحسب أدناه
                    'count' => (clone $transactions)->count(),
                    'commission_earned' => (float) (clone $transactions)
                        ->sum('commission'),
                ];
            }
        );
    }

    /**
     * حساب العمولة المتراكمة لفترة محددة
     */
    public function getCommissionAccrual(
        Agent $agent,
        string $startDate,
        string $endDate,
    ): array {
        $commissions = AgentTransaction::where('agent_id', $agent->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("
                DATE(created_at) as date,
                SUM(commission) as daily_commission,
                COUNT(*) as transaction_count
            ")
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return [
            'total_commission' => (float) $commissions->sum('daily_commission'),
            'daily_breakdown' => $commissions->map(fn($row) => [
                'date' => $row->date,
                'commission' => (float) $row->daily_commission,
                'transactions' => (int) $row->transaction_count,
            ]),
            'currency' => 'SAR',
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    /**
     * إنشاء لقطة يومية للحساب
     */
    public function createDailySnapshot(Agent $agent): AgentDailySnapshot
    {
        return DB::transaction(function () use ($agent) {
            $wallet = $agent->wallet()->lockForUpdate()->firstOrFail();
            $today = now()->toDateString();

            $existingSnapshot = AgentDailySnapshot::where('agent_id', $agent->id)
                ->where('snapshot_date', $today)
                ->first();

            if ($existingSnapshot) {
                return $existingSnapshot;
            }

            $yesterday = AgentDailySnapshot::where('agent_id', $agent->id)
                ->where('snapshot_date', now()->subDay()->toDateString())
                ->first();

            $openingBalance = $yesterday ? $yesterday->closing_balance : 0;

            $dailyData = AgentTransaction::where('agent_id', $agent->id)
                ->whereDate('created_at', $today)
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN type = 'cash_in' THEN amount ELSE 0 END), 0) as cash_in,
                    COALESCE(SUM(CASE WHEN type = 'cash_out' THEN amount ELSE 0 END), 0) as cash_out,
                    COALESCE(SUM(commission), 0) as commission,
                    COUNT(*) as tx_count
                ")
                ->first();

            $snapshot = AgentDailySnapshot::create([
                'agent_id' => $agent->id,
                'snapshot_date' => $today,
                'opening_balance' => $openingBalance,
                'closing_balance' => $wallet->balance,
                'total_cash_in' => $dailyData->cash_in,
                'total_cash_out' => $dailyData->cash_out,
                'total_commission' => $dailyData->commission,
                'transaction_count' => $dailyData->tx_count,
            ]);

            Log::info('تم إنشاء لقطة يومية للوكيل', [
                'agent_id' => $agent->id,
                'date' => $today,
                'balance' => $wallet->balance,
            ]);

            return $snapshot;
        });
    }

    /**
     * الحصول على سلسلة زمنية للأرصدة (للرسوم البيانية)
     */
    public function getBalanceHistory(
        Agent $agent,
        int $days = 30,
    ): array {
        $snapshots = AgentDailySnapshot::where('agent_id', $agent->id)
            ->where('snapshot_date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('snapshot_date')
            ->get()
            ->map(fn($s) => [
                'date' => $s->snapshot_date,
                'opening_balance' => (float) $s->opening_balance,
                'closing_balance' => (float) $s->closing_balance,
                'cash_in' => (float) $s->total_cash_in,
                'cash_out' => (float) $s->total_cash_out,
                'commission' => (float) $s->total_commission,
                'transactions' => (int) $s->transaction_count,
            ]);

        if ($snapshots->isEmpty()) {
            $wallet = $agent->wallet;
            $snapshots = collect([
                [
                    'date' => now()->toDateString(),
                    'opening_balance' => 0,
                    'closing_balance' => (float) ($wallet?->balance ?? 0),
                    'cash_in' => 0,
                    'cash_out' => 0,
                    'commission' => 0,
                    'transactions' => 0,
                ],
            ]);
        }

        return [
            'agent_id' => $agent->id,
            'snapshots' => $snapshots,
            'meta' => [
                'period_days' => $days,
                'from' => now()->subDays($days)->toDateString(),
                'to' => now()->toDateString(),
            ],
        ];
    }

    /**
     * الحصول على ملخص سريع للوحة التحكم
     */
    public function getQuickSummary(Agent $agent): array
    {
        $wallet = $agent->wallet;
        $today = now()->toDateString();

        $todayData = AgentTransaction::where('agent_id', $agent->id)
            ->whereDate('created_at', $today)
            ->selectRaw("
                COUNT(*) as total_transactions,
                COALESCE(SUM(CASE WHEN type = 'cash_in' THEN amount ELSE 0 END), 0) as today_cash_in,
                COALESCE(SUM(CASE WHEN type = 'cash_out' THEN amount ELSE 0 END), 0) as today_cash_out,
                COALESCE(SUM(commission), 0) as today_commission
            ")
            ->first();

        return [
            'balance' => (float) ($wallet?->balance ?? 0),
            'today' => [
                'cash_in' => (float) ($todayData->today_cash_in ?? 0),
                'cash_out' => (float) ($todayData->today_cash_out ?? 0),
                'commission' => (float) ($todayData->today_commission ?? 0),
                'transactions' => (int) ($todayData->total_transactions ?? 0),
            ],
            'limits' => [
                'max_daily' => (float) $agent->max_daily_limit,
                'used_today' => (float) $agent->current_daily_total,
                'remaining' => (float) ($agent->max_daily_limit - $agent->current_daily_total),
            ],
            'total_commission_earned' => (float) ($wallet?->total_commission_earned ?? 0),
        ];
    }
}
```

## ملخص العمليات

| العملية | الوصف | مصدر البيانات | مدة التخزين المؤقت |
|---------|-------|---------------|-------------------|
| Daily Totals | إجمالي الإيداع والسحب ليوم محدد | agent_transactions | 5 دقائق |
| Commission Accrual | تفصيل العمولات اليومية لفترة | agent_transactions | لا تخزين مؤقت |
| Daily Snapshot | لقطة يومية لرصيد الوكيل | agent_daily_snapshots | دائم (بيانات تاريخية) |
| Balance History | سلسلة زمنية للأرصدة (30 يوماً) | agent_daily_snapshots | 10 دقائق |
| Quick Summary | ملخص سريع للوحة التحكم | agent_wallets + agent_transactions | دقيقة واحدة |
