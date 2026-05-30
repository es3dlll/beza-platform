<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Settlement\Services\SettlementService;

final class SettlementProcessDaily extends Command
{
    protected $signature = 'settlement:process-daily';
    protected $description = 'Process daily settlement cutoff';

    public function handle(SettlementService $settlement): int
    {
        $result = $settlement->processDailyCutoff();
        $this->info("Daily settlement processed: {$result['total']} entries");
        return self::SUCCESS;
    }
}
