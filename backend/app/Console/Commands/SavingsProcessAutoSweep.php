<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Savings\Services\AutoSweep;

final class SavingsProcessAutoSweep extends Command
{
    protected $signature = 'savings:auto-sweep';
    protected $description = 'Process auto-sweep for savings goals';

    public function handle(AutoSweep $autoSweep): int
    {
        $count = $autoSweep->process();
        $this->info("Auto-sweep processed for {$count} goals");
        return self::SUCCESS;
    }
}
