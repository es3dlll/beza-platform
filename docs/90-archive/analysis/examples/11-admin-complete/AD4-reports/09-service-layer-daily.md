# 09 - DailyReportService

```php
<?php
// app/Services/Admin/Reports/DailyReportService.php

namespace App\Services\Admin\Reports;

use App\DTOs\Admin\DailyReportData;
use App\Models\Admin\DailyReport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    public function generate(string $date): DailyReportData
    {
        // محاولة قراءة من التقرير المخزن
        $cached = DailyReport::where('date', $date)->first();
        if ($cached) {
            return $this->toDTO($cached);
        }

        // توليد التقرير من DB
        $report = $this->generateFromDB($date);

        // تخزين للتسريع مستقبلاً
        $this->storeReport($report);

        return $report;
    }

    private function generateFromDB(string $date): DailyReportData
    {
        $transactions = Transaction::whereDate('created_at', $date)
            ->where('status', 'completed');

        $fees = Transaction::whereDate('created_at', $date)
            ->where('type', 'fee')
            ->where('status', 'completed');

        $breakdown = Transaction::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as volume'))
            ->groupBy('type')
            ->get()
            ->pluck(null, 'type')
            ->toArray();

        $newUsers = User::whereDate('created_at', $date)
            ->whereNull('deleted_at')
            ->count();

        $activeUsers = Transaction::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->distinct('from_wallet_id')
            ->count('from_wallet_id');

        // مقارنة باليوم السابق
        $prevDate = Carbon::parse($date)->subDay()->toDateString();
        $prevVolume = Transaction::whereDate('created_at', $prevDate)
            ->where('status', 'completed')
            ->sum('amount');

        $currentVolume = (float) $transactions->sum('amount');
        $growth = $prevVolume > 0
            ? round((($currentVolume - $prevVolume) / $prevVolume) * 100, 1)
            : null;

        return new DailyReportData(
            date: $date,
            totalTransactions: $transactions->count(),
            totalVolume: $currentVolume,
            totalFees: (float) $fees->sum('amount'),
            newUsers: $newUsers,
            activeUsers: $activeUsers,
            transactionBreakdown: $breakdown,
            growthPercent: $growth,
        );
    }

    public function generateForDate(string $date): void
    {
        $report = $this->generateFromDB($date);
        $this->storeReport($report);
    }

    private function storeReport(DailyReportData $data): void
    {
        DailyReport::updateOrCreate(
            ['date' => $data->date],
            [
                'total_transactions'    => $data->totalTransactions,
                'total_volume'          => $data->totalVolume,
                'total_fees'            => $data->totalFees,
                'new_users'             => $data->newUsers,
                'active_users'          => $data->activeUsers,
                'transaction_breakdown' => $data->transactionBreakdown,
            ]
        );
    }

    private function toDTO(DailyReport $model): DailyReportData
    {
        return new DailyReportData(
            date: $model->date->toDateString(),
            totalTransactions: $model->total_transactions,
            totalVolume: (float) $model->total_volume,
            totalFees: (float) $model->total_fees,
            newUsers: $model->new_users,
            activeUsers: $model->active_users,
            transactionBreakdown: $model->transaction_breakdown ?? [],
            growthPercent: null,
        );
    }
}
```
