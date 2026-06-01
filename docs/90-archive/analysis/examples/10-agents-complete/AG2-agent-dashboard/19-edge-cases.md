# 19 - حالات الحافة (Edge Cases) - لوحة تحكم الوكيل

## 1. اليوم الأول - لا توجد بيانات

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentDailySnapshot;
use App\Models\AgentTransaction;
use Illuminate\Support\Facades\Log;

class FirstDayDataHandler
{
    /**
     * معالجة حالة عدم وجود بيانات لليوم الأول
     */
    public function handleNoDataCase(Agent $agent): array
    {
        $snapshotCount = AgentDailySnapshot::where('agent_id', $agent->id)->count();
        $transactionCount = AgentTransaction::where('agent_id', $agent->id)->count();

        if ($snapshotCount === 0 && $transactionCount === 0) {
            Log::info('وكيل جديد - لا توجد بيانات بعد', [
                'agent_id' => $agent->id,
                'created_at' => $agent->created_at,
            ]);

            return [
                'is_new_agent' => true,
                'days_active' => 0,
                'message' => 'مرحباً بك في لوحة التحكم! لا توجد معاملات بعد. ابدأ بتقديم الخدمات لعملائك.',
                'wallet_balance' => (float) ($agent->wallet?->balance ?? 0),
                'stats' => [
                    'total_cash_in' => 0,
                    'total_cash_out' => 0,
                    'total_commission' => 0,
                    'transaction_count' => 0,
                ],
                'balance_history' => [
                    [
                        'date' => now()->toDateString(),
                        'opening_balance' => 0,
                        'closing_balance' => (float) ($agent->wallet?->balance ?? 0),
                        'cash_in' => 0,
                        'cash_out' => 0,
                    ],
                ],
            ];
        }

        return [];
    }

    /**
     * معالجة طلب إحصائيات ليوم لم تكن فيه معاملات
     */
    public function handleEmptyDay(string $date): array
    {
        return [
            'date' => $date,
            'total_cash_in' => 0,
            'total_cash_out' => 0,
            'net' => 0,
            'count' => 0,
            'commission_earned' => 0,
            'message' => 'لا توجد معاملات في هذا اليوم.',
        ];
    }
}
```

## 2. أحجام معاملات كبيرة (Large Transaction Volumes)

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\CursorPaginator;

class LargeVolumeHandler
{
    private const BATCH_SIZE = 1000;
    private const MAX_PAGINATION = 10000;

    /**
     * معالجة أحجام المعاملات الكبيرة باستخدام Cursor Pagination
     */
    public function paginateLargeTransactionSet(
        Agent $agent,
        array $filters,
        int $perPage = 50,
    ): CursorPaginator {
        $query = AgentTransaction::where('agent_id', $agent->id)
            ->select([
                'id', 'type', 'amount', 'commission', 'net_amount',
                'balance_before', 'balance_after', 'status',
                'created_at', 'reference_number',
            ])
            ->when($filters['type'] ?? null, fn($q, $t) => $q->where('type', $t))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderBy('created_at', 'desc');

        return $query->cursorPaginate(min($perPage, 100));
    }

    /**
     * تجميع الإحصائيات اليومية للكميات الكبيرة (معالجة مجمعة)
     */
    public function aggregateLargeVolumeDaily(Agent $agent, string $date): array
    {
        return DB::transaction(function () use ($agent, $date) {
            $results = AgentTransaction::where('agent_id', $agent->id)
                ->whereDate('created_at', $date)
                ->selectRaw("
                    COUNT(*) as total_count,
                    COALESCE(SUM(CASE WHEN type = 'cash_in' THEN amount ELSE 0 END), 0) as cash_in_total,
                    COALESCE(SUM(CASE WHEN type = 'cash_out' THEN amount ELSE 0 END), 0) as cash_out_total,
                    COALESCE(SUM(commission), 0) as commission_total,
                    MIN(created_at) as first_transaction,
                    MAX(created_at) as last_transaction
                ")
                ->first();

            if ((int) $results->total_count > self::BATCH_SIZE) {
                Log::info('حجم معاملات كبير لليوم', [
                    'agent_id' => $agent->id,
                    'date' => $date,
                    'count' => $results->total_count,
                ]);
            }

            return [
                'total_transactions' => (int) $results->total_count,
                'total_cash_in' => (float) $results->cash_in_total,
                'total_cash_out' => (float) $results->cash_out_total,
                'total_commission' => (float) $results->commission_total,
                'first_at' => $results->first_transaction,
                'last_at' => $results->last_transaction,
                'is_large_volume' => (int) $results->total_count > self::BATCH_SIZE,
            ];
        });
    }

    /**
     * معالجة الإحصائيات التراكمية للكميات الكبيرة
     */
    public function getAccumulativeStats(Agent $agent): array
    {
        return Cache::remember("agent_accumulative_{$agent->id}", 600, function () use ($agent) {
            return AgentTransaction::where('agent_id', $agent->id)
                ->selectRaw("
                    COUNT(*) as lifetime_transactions,
                    COALESCE(SUM(CASE WHEN type = 'cash_in' THEN amount ELSE 0 END), 0) as lifetime_cash_in,
                    COALESCE(SUM(CASE WHEN type = 'cash_out' THEN amount ELSE 0 END), 0) as lifetime_cash_out,
                    COALESCE(SUM(commission), 0) as lifetime_commission,
                    MIN(created_at) as first_transaction_date,
                    MAX(created_at) as last_transaction_date
                ")
                ->first();
        });
    }
}
```

## 3. اختلاف المنطقة الزمنية (Timezone Mismatches)

```php
<?php

namespace App\Services;

use App\Models\Agent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TimezoneHandler
{
    private const DEFAULT_TIMEZONE = 'Asia/Riyadh';
    private const ACCEPTED_TIMEZONES = [
        'Asia/Riyadh',
        'Asia/Kuwait',
        'Asia/Qatar',
        'Asia/Dubai',
        'Asia/Bahrain',
        'Asia/Muscat',
        'Asia/Aden',
    ];

    /**
     * التعامل مع فروقات المناطق الزمنية
     */
    public function normalizeDateRange(
        ?string $dateFrom,
        ?string $dateTo,
        ?string $timezone = null,
    ): array {
        $tz = $this->resolveTimezone($timezone);

        $from = $dateFrom
            ? Carbon::parse($dateFrom, $tz)->startOfDay()->setTimezone('UTC')
            : now($tz)->startOfDay()->setTimezone('UTC');

        $to = $dateTo
            ? Carbon::parse($dateTo, $tz)->endOfDay()->setTimezone('UTC')
            : now($tz)->endOfDay()->setTimezone('UTC');

        Log::debug('تحويل المنطقة الزمنية للاستعلام', [
            'requested_timezone' => $tz,
            'utc_from' => $from->toIso8601String(),
            'utc_to' => $to->toIso8601String(),
        ]);

        return [
            'date_from' => $from,
            'date_to' => $to,
            'timezone' => $tz,
            'utc_offset' => now($tz)->getOffset() / 3600,
        ];
    }

    /**
     * تحويل إحصائيات اليوم إلى المنطقة الزمنية للوكيل
     */
    public function convertDailyStatsToTimezone(array $stats, string $timezone): array
    {
        $tz = $this->resolveTimezone($timezone);

        if (isset($stats['date'])) {
            $stats['date'] = Carbon::parse($stats['date'])
                ->setTimezone($tz)
                ->toDateString();
        }

        if (isset($stats['snapshots'])) {
            $stats['snapshots'] = collect($stats['snapshots'])->map(function ($snapshot) use ($tz) {
                $snapshot['date'] = Carbon::parse($snapshot['date'])
                    ->setTimezone($tz)
                    ->toDateString();
                return $snapshot;
            })->toArray();
        }

        return $stats;
    }

    /**
     * التحقق من تطابق المنطقة الزمنية للوكيل مع المنطقة الافتراضية
     */
    public function validateAgentTimezone(Agent $agent): void
    {
        $agentTimezone = $agent->timezone ?? self::DEFAULT_TIMEZONE;

        if (!in_array($agentTimezone, self::ACCEPTED_TIMEZONES)) {
            Log::warning('منطقة زمنية غير معتادة للوكيل', [
                'agent_id' => $agent->id,
                'timezone' => $agentTimezone,
            ]);
        }
    }

    private function resolveTimezone(?string $timezone): string
    {
        if ($timezone && in_array($timezone, self::ACCEPTED_TIMEZONES)) {
            return $timezone;
        }
        return self::DEFAULT_TIMEZONE;
    }
}
```

## 4. سيناريوهات إضافية

| الحالة | المشكلة | الحل |
|--------|---------|------|
| لا توجد بيانات (وكيل جديد) | لوحة تحكم فارغة | عرض رسالة ترحيب مع تعليمات البدء |
| أحجام معاملات كبيرة | بطء التحميل | Cursor Pagination + تجميع مسبق |
| اختلاف المنطقة الزمنية | إحصائيات يوم غير صحيحة | تحويل التواريخ إلى UTC ثم عرضها بتوقيت الوكيل |
| طلب نطاق زمني كبير جداً (>365 يوم) | استعلام بطيء | رفض الطلب مع حد أقصى 365 يوماً |
| انتهاء صلاحية التخزين المؤقت | بيانات قديمة | تخزين مؤقت قصير للرصيد الفوري (دقيقة) |
| معاملة معلقة | رصيد غير دقيق | عرض رصيد معلق منفصل عن الرصيد المتاح |
