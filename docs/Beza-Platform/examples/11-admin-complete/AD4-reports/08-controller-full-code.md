# 08 - المتحكم الكامل (Controller)

## ReportController

```php
<?php
// app/Http/Controllers/Api/Admin/ReportController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Http\Resources\Admin\DailyReportResource;
use App\Http\Resources\Admin\MonthlyReportResource;
use App\Http\Resources\Admin\FinancialReportResource;
use App\Services\Admin\Reports\DailyReportService;
use App\Services\Admin\Reports\MonthlyReportService;
use App\Services\Admin\Reports\FinancialReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly DailyReportService    $dailyService,
        private readonly MonthlyReportService  $monthlyService,
        private readonly FinancialReportService $financialService,
    ) {}

    public function daily(ReportFilterRequest $request): JsonResponse
    {
        $date = $request->input('date', today()->toDateString());

        $report = $this->dailyService->generate($date);

        return response()->json([
            'success' => true,
            'data'    => new DailyReportResource($report),
        ]);
    }

    public function monthly(ReportFilterRequest $request): JsonResponse
    {
        $year  = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $report = $this->monthlyService->generate($year, $month);

        return response()->json([
            'success' => true,
            'data'    => new MonthlyReportResource($report),
        ]);
    }

    public function financial(ReportFilterRequest $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        $report = $this->financialService->generate($from, $to);

        return response()->json([
            'success' => true,
            'data'    => new FinancialReportResource($report),
        ]);
    }
}
```

## المسارات (Routes)

```php
Route::middleware(['auth:api', 'admin'])
    ->prefix('admin/reports')
    ->group(function () {
        Route::get('/daily', [ReportController::class, 'daily']);
        Route::get('/monthly', [ReportController::class, 'monthly']);
        Route::get('/financial', [ReportController::class, 'financial']);
    });
```

## DailyReportResource

```php
<?php
// app/Http/Resources/Admin/DailyReportResource.php

class DailyReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date'                  => $this->date,
            'total_transactions'    => $this->totalTransactions,
            'total_volume'          => $this->totalVolume,
            'total_fees'            => $this->totalFees,
            'new_users'             => $this->newUsers,
            'active_users'          => $this->activeUsers,
            'avg_transaction'       => $this->totalTransactions > 0
                ? round($this->totalVolume / $this->totalTransactions, 2) : 0,
            'transaction_breakdown' => $this->transactionBreakdown,
            'growth_percent'        => $this->growthPercent,
            'generated_at'          => now()->toIso8601String(),
        ];
    }
}
```

## مثال الاستجابة

```json
{
    "success": true,
    "data": {
        "date": "2026-05-27",
        "total_transactions": 1542,
        "total_volume": 1250000.00,
        "total_fees": 45200.00,
        "new_users": 89,
        "active_users": 3450,
        "avg_transaction": 810.64,
        "transaction_breakdown": {
            "transfer": {"count": 800, "volume": 650000},
            "deposit": {"count": 400, "volume": 400000},
            "withdraw": {"count": 200, "volume": 150000},
            "merchant_payment": {"count": 142, "volume": 50000}
        },
        "growth_percent": 5.2,
        "generated_at": "2026-05-27T23:55:00+03:00"
    }
}
```
