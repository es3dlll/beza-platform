<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class FraudRefreshRulesCache extends Command
{
    protected $signature = 'fraud:refresh-cache';
    protected $description = 'Refresh fraud rules cache';

    public function handle(): int
    {
        Cache::forget('fraud:active_rules');
        $this->info('Fraud rules cache refreshed');
        return self::SUCCESS;
    }
}
