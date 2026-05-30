<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Savings\Services\ProfitDistribution;

final class SavingsDistributeProfits extends Command
{
    protected $signature = 'savings:distribute-profits';
    protected $description = 'Distribute monthly profits to savings accounts';

    public function handle(ProfitDistribution $profitDistribution): int
    {
        $count = $profitDistribution->distribute();
        $this->info("Profits distributed to {$count} accounts");
        return self::SUCCESS;
    }
}
