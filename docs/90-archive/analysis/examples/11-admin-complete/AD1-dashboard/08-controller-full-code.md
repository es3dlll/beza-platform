# 08 - المتحكم الكامل للوحة التحكم (Controller Full Code)

## AdminDashboardController

```php
<?php
// app/Http/Controllers/Api/Admin/DashboardController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\DashboardResource;
use App\Services\Admin\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $statsService
    ) {}

    /**
     * GET /api/v1/admin/dashboard/stats
     *
     * إحصائيات لوحة التحكم الرئيسية
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->input('period', '30d');

        $stats = $this->statsService->getStats($period);

        return response()->json([
            'success' => true,
            'data'    => new DashboardResource($stats),
            'meta'    => [
                'cached_at'  => $stats['cached_at'],
                'expires_in' => $stats['expires_in'],
            ],
        ]);
    }

    /**
     * POST /api/v1/admin/dashboard/refresh
     *
     * إجبار إعادة تحميل البيانات (مسح cache)
     */
    public function refresh(): JsonResponse
    {
        $this->statsService->clearCache();

        $stats = $this->statsService->getStats('30d');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البيانات',
            'data'    => new DashboardResource($stats),
        ]);
    }
}
```

## DashboardResource

```php
<?php
// app/Http/Resources/Admin/DashboardResource.php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'summary' => [
                'total_users'          => (int) $this['total_users'],
                'active_users'         => (int) $this['active_users'],
                'total_transactions'   => (int) $this['total_transactions'],
                'transaction_volume'   => (float) $this['transaction_volume'],
                'total_wallets_balance'=> (float) $this['total_wallets_balance'],
                'merchants_count'      => (int) $this['merchants_count'],
                'agents_count'         => (int) $this['agents_count'],
                'total_fees'           => (float) $this['total_fees'],
            ],
            'charts' => [
                'revenue'     => $this['revenue_chart'],
                'volume'      => $this['volume_chart'],
                'user_growth' => $this['user_growth_chart'],
                'daily_active'=> $this['daily_active_chart'],
            ],
            'top_merchants' => $this['top_merchants'],
            'recent_activities' => $this['recent_activities'] ?? [],
        ];
    }
}
```

## المسار (Route)

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\Admin\DashboardController;

Route::middleware(['auth:api', 'admin', 'throttle:60,1'])
    ->prefix('admin/dashboard')
    ->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::post('/refresh', [DashboardController::class, 'refresh']);
    });
```

## مثال الاستجابة

```json
{
    "success": true,
    "data": {
        "summary": {
            "total_users": 15420,
            "active_users": 8910,
            "total_transactions": 284500,
            "transaction_volume": 12500000.00,
            "total_wallets_balance": 8750000.00,
            "merchants_count": 342,
            "agents_count": 89,
            "total_fees": 452000.00
        },
        "charts": {
            "revenue": [
                {"date": "2026-04-27", "value": 12500},
                {"date": "2026-04-28", "value": 13200}
            ],
            "volume": [
                {"date": "2026-04-27", "value": 450000},
                {"date": "2026-04-28", "value": 520000}
            ],
            "user_growth": [
                {"date": "2026-04-27", "new_users": 120},
                {"date": "2026-04-28", "new_users": 145}
            ],
            "daily_active": [
                {"date": "2026-04-27", "count": 3200},
                {"date": "2026-04-28", "count": 3450}
            ]
        },
        "top_merchants": [
            {"id": 1, "name": "متجر الإلكترونيات", "volume": 850000, "transactions": 1200},
            {"id": 2, "name": "مكتبة نور", "volume": 620000, "transactions": 950}
        ]
    },
    "meta": {
        "cached_at": "2026-05-27T14:30:00+03:00",
        "expires_in": 240
    }
}
```
