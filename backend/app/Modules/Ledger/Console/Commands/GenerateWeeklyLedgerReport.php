<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Console\Commands;

use App\Modules\Ledger\Models\ReconciliationReport;
use Illuminate\Console\Command;

final class GenerateWeeklyLedgerReport extends Command
{
    protected $signature = 'ledger:weekly-report {--email=}';
    protected $description = 'Generate weekly ledger performance report';

    public function handle(): int
    {
        $this->info('Generating weekly ledger report...');

        $reports = ReconciliationReport::where('created_at', '>=', now()->subWeek())->get();
        $total = $reports->count();
        $balanced = $reports->where('is_balanced', true)->count();
        $avgTime = $reports->avg('execution_time_ms');
        $critical = $reports->sum('total_discrepancies_found');

        $this->table(['Metric', 'Value'], [
            ['Total Reconciliations', $total],
            ['Success Rate', $total > 0 ? round(($balanced / $total) * 100, 2) . '%' : 'N/A'],
            ['Avg Execution Time', round($avgTime ?? 0, 2) . ' ms'],
            ['Total Discrepancies', $critical],
        ]);

        $this->info('Weekly report generated successfully.');

        return self::SUCCESS;
    }
}
