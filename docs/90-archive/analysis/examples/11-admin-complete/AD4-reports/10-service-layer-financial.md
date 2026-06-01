# 10 - FinancialReportService

```php
<?php
// app/Services/Admin/Reports/FinancialReportService.php

namespace App\Services\Admin\Reports;

use App\Models\Admin\OperationalCost;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function generate(string $from, string $to): array
    {
        return [
            'period' => [
                'from' => $from,
                'to'   => $to,
            ],
            'revenue'    => $this->getRevenue($from, $to),
            'costs'      => $this->getCosts($from, $to),
            'profit_loss'=> $this->calculatePL($from, $to),
            'fees_analysis' => $this->getFeesAnalysis($from, $to),
            'summary'    => $this->getSummary($from, $to),
        ];
    }

    private function getRevenue(string $from, string $to): array
    {
        $revenueByType = Transaction::whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->whereIn('type', ['fee', 'exchange_profit', 'merchant_commission'])
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        return [
            'total'    => array_sum($revenueByType),
            'breakdown'=> $revenueByType,
        ];
    }

    private function getCosts(string $from, string $to): array
    {
        $costs = OperationalCost::whereBetween('date', [$from, $to])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        return [
            'total'    => array_sum($costs),
            'breakdown'=> $costs,
        ];
    }

    private function calculatePL(string $from, string $to): array
    {
        $revenue = $this->getRevenue($from, $to)['total'];
        $costs   = $this->getCosts($from, $to)['total'];

        $profit = $revenue - $costs;
        $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

        return [
            'revenue'          => $revenue,
            'costs'            => $costs,
            'profit'           => $profit,
            'profit_margin'    => $margin,
        ];
    }

    private function getFeesAnalysis(string $from, string $to): array
    {
        return Transaction::whereBetween('created_at', [$from, $to])
            ->where('type', 'fee')
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as daily_fees')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getSummary(string $from, string $to): array
    {
        $totalVolume = Transaction::whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->sum('amount');

        $totalTransactions = Transaction::whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->count();

        return [
            'total_volume'       => (float) $totalVolume,
            'total_transactions' => $totalTransactions,
            'avg_transaction'    => $totalTransactions > 0
                ? round((float) $totalVolume / $totalTransactions, 2) : 0,
        ];
    }
}
```
