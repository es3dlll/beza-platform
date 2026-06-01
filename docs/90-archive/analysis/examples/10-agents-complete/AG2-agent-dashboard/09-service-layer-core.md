# 09 - سيرفس لير العملية (Service Layer)

## AgentDashboardService

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentTransaction;
use Illuminate\Support\Facades\Cache;

class AgentDashboardService
{
    public function getStats(Agent $agent): array
    {
        $today = now()->startOfDay();
        $todayTransactions = AgentTransaction::where('agent_id', $agent->id)
            ->where('created_at', '>=', $today);

        return [
            'total_transactions' => AgentTransaction::where('agent_id', $agent->id)->count(),
            'total_volume' => AgentTransaction::where('agent_id', $agent->id)
                ->where('status', 'completed')
                ->sum('amount'),
            'commission_earned' => AgentTransaction::where('agent_id', $agent->id)
                ->where('status', 'completed')
                ->sum('commission_earned'),
            'today_count' => (clone $todayTransactions)->count(),
            'today_volume' => (clone $todayTransactions)->sum('amount'),
            'available' => $agent->available,
            'rating' => $agent->rating ?? 0,
            'cash_balance_syp' => $agent->cash_balance_syp,
            'cash_balance_usd' => $agent->cash_balance_usd,
            'activities' => $this->getRecentActivities($agent),
        ];
    }

    private function getRecentActivities(Agent $agent): array
    {
        return AgentTransaction::where('agent_id', $agent->id)
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'currency' => $t->currency,
                'customer_name' => $t->user->name,
                'commission' => $t->commission_earned,
                'created_at' => $t->created_at,
            ])
            ->toArray();
    }
}
```

## تدفق الخدمة

1. جلب إحصائيات الوكيل الإجمالية (عدد المعاملات، حجم التداول، العمولات)
2. جلب إحصائيات اليوم (عدد وحجم معاملات اليوم)
3. جلب آخر 20 نشاطاً
4. إرجاع البيانات مع حالة التوفر والأرصدة النقدية
