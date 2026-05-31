<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Events;

use App\Modules\Ledger\Models\ReconciliationReport;
use Illuminate\Foundation\Events\Dispatchable;

final class ReconciliationCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly ReconciliationReport $report,
    ) {}

    public function hasDiscrepancies(): bool
    {
        return $this->report->total_discrepancies_found > 0;
    }

    public function isCBSReport(): bool
    {
        return in_array($this->report->report_type, [
            ReconciliationReport::TYPE_DAILY_TRIAL_BALANCE,
            ReconciliationReport::TYPE_SETTLEMENT_REPORT,
            ReconciliationReport::TYPE_BALANCE_SHEET,
            ReconciliationReport::TYPE_INCOME_STATEMENT,
        ]);
    }
}
