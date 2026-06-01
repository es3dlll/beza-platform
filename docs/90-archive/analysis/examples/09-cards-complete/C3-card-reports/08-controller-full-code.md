# 08 - كود المتحكم الكامل (Controller Full Code)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CardReportRequest;
use App\Models\Card;
use App\Services\CardReportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CardReportController extends Controller
{
    public function __construct(
        private readonly CardReportService $reportService
    ) {}

    public function transactions(CardReportRequest $request, Card $card): JsonResponse
    {
        $this->authorize('view', $card);

        $transactions = $this->reportService->getTransactions(
            $card,
            $request->validated()
        );

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function spending(CardReportRequest $request, Card $card): JsonResponse
    {
        $this->authorize('view', $card);

        $breakdown = $this->reportService->getSpendingBreakdown(
            $card,
            $request->input('period', 'monthly')
        );

        return response()->json(['data' => $breakdown]);
    }

    public function fees(CardReportRequest $request, Card $card): JsonResponse
    {
        $this->authorize('view', $card);

        $from = $request->date('date_from', now()->subMonth());
        $to = $request->date('date_to', now());

        $summary = $this->reportService->getFeeSummary($card, $from, $to);

        return response()->json(['data' => $summary]);
    }

    public function export(CardReportRequest $request, Card $card): StreamedResponse
    {
        $this->authorize('view', $card);

        $csv = $this->reportService->exportCsv(
            $card,
            $request->validated()
        );

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, "card-report-{$card->id}-" . now()->format('Ymd') . '.csv', [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment',
        ]);
    }
}
```

## Routes (api.php)

```php
Route::get('/cards/{card}/reports/transactions', [CardReportController::class, 'transactions']);
Route::get('/cards/{card}/reports/spending', [CardReportController::class, 'spending']);
Route::get('/cards/{card}/reports/fees', [CardReportController::class, 'fees']);
Route::get('/cards/{card}/reports/export', [CardReportController::class, 'export']);
```

## Endpoints Summary

| Method | URI | Action |
|--------|-----|--------|
| GET | /api/v1/cards/{card}/reports/transactions | Transaction history with filters |
| GET | /api/v1/cards/{card}/reports/spending | Spending breakdown by category/month |
| GET | /api/v1/cards/{card}/reports/fees | Fee summary for a date range |
| GET | /api/v1/cards/{card}/reports/export | CSV export download |
