# 09 - DealService كامل

## AdminDealService

```php
<?php
// app/Services/AdminDealService.php

namespace App\Services;

use App\Events\DealCreated;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDealService
{
    /**
     * إنشاء صفقة جديدة
     */
    public function create(User $admin, array $data): Deal
    {
        $deal = DB::transaction(function () use ($admin, $data) {
            return Deal::create([
                'created_by'                  => $admin->id,
                'title'                       => $data['title'],
                'description'                 => $data['description'] ?? null,
                'target_amount'               => $data['target_amount'],
                'current_amount'              => 0,
                'currency'                    => $data['currency'],
                'expected_profit_percentage'  => $data['expected_profit_percentage'],
                'duration_days'               => $data['duration_days'],
                'category'                    => $data['category'],
                'risk_level'                  => $data['risk_level'],
                'status'                      => 'pending',
            ]);
        });

        try {
            DealCreated::dispatch($deal, $admin);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال إشعار إنشاء الصفقة', [
                'deal_id' => $deal->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return $deal;
    }

    /**
     * تغيير حالة الصفقة (pending → active)
     */
    public function activate(Deal $deal): Deal
    {
        if ($deal->status !== 'pending') {
            throw new \RuntimeException('يمكن تفعيل الصفقات ذات الحالة pending فقط');
        }

        $deal->update([
            'status'    => 'active',
            'starts_at' => now(),
        ]);

        return $deal;
    }
}
```

## DealService (عام)

```php
<?php
// app/Services/DealService.php

namespace App\Services;

use App\Models\Deal;
use Illuminate\Support\Facades\DB;

class DealService
{
    /**
     * الحصول على الصفقات المتاحة للاستثمار
     */
    public function getAvailableDeals()
    {
        return Deal::available()->latest()->paginate(20);
    }

    /**
     * الحصول على تفاصيل صفقة مع المستثمرين
     */
    public function getDealWithInvestors(Deal $deal): Deal
    {
        return $deal->load(['investments.investor', 'creator']);
    }
}
```
