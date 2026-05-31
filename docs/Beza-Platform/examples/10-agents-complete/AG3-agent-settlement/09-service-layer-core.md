# 09 - سيرفس لير العملية (Service Layer)

## AgentSettlementService

```php
<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentSettlement;
use App\Events\SettlementRequested;
use Illuminate\Support\Facades\DB;

class AgentSettlementService
{
    public function requestSettlement(Agent $agent, array $data): array
    {
        return DB::transaction(function () use ($agent, $data) {
            // التحقق من رصيد العمولات
            $commissionBalance = $agent->agentTransactions()
                ->where('status', 'completed')
                ->sum('commission_earned');

            $pendingSettlements = AgentSettlement::where('agent_id', $agent->id)
                ->whereIn('status', ['pending', 'processing'])
                ->sum('amount');

            $availableBalance = $commissionBalance - $pendingSettlements;

            if ($data['amount'] > $availableBalance) {
                throw new \Exception('الرصيد المتاح للتسوية غير كافٍ');
            }

            // حساب الرسوم (مثال: 1% من المبلغ)
            $fee = $data['amount'] * 0.01;
            $netAmount = $data['amount'] - $fee;

            // إنشاء طلب التسوية
            $settlement = AgentSettlement::create([
                'agent_id' => $agent->id,
                'amount' => $data['amount'],
                'fee' => $fee,
                'net_amount' => $netAmount,
                'bank_account_id' => $data['bank_account_id'],
                'status' => 'pending',
            ]);

            // إطلاق حدث
            event(new SettlementRequested($settlement, $agent));

            return [
                'settlement_id' => $settlement->id,
                'amount' => $settlement->amount,
                'fee' => $settlement->fee,
                'net_amount' => $settlement->net_amount,
                'status' => 'pending',
            ];
        }, attempts: 3);
    }
}
```

## تدفق الخدمة

1. التحقق من رصيد العمولات المتاح (الفرق بين الأرباح والتسويات المعلقة)
2. التحقق من أن المبلغ لا يتجاوز الرصيد المتاح
3. حساب الرسوم (1% افتراضياً)
4. إنشاء طلب تسوية بحالة pending
5. إطلاق حدث SettlementRequested لإشعار الإدارة
6. إرجاع تفاصيل الطلب
