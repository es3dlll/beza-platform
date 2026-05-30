<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ledger\Services\TrialBalanceService;

final class LedgerCloseDaily extends Command
{
    protected $signature = 'ledger:close-daily {date?}';
    protected $description = 'Close daily trial balance for the ledger';

    public function handle(TrialBalanceService $trialBalance): int
    {
        $date = $this->argument('date') ?? now()->subDay()->format('Y-m-d');
        $result = $trialBalance->generate($date);
        $this->info("Daily close for {$date}: {$result['total_debits']} / {$result['total_credits']}");
        return self::SUCCESS;
    }
}
