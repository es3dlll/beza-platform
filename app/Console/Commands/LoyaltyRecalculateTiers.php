<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Loyalty\Services\TierService;

final class LoyaltyRecalculateTiers extends Command
{
    protected $signature = 'loyalty:recalculate-tiers';
    protected $description = 'Recalculate loyalty tiers for all users';

    public function handle(TierService $tierService): int
    {
        $count = $tierService->recalculateAll();
        $this->info("Recalculated tiers for {$count} users");
        return self::SUCCESS;
    }
}
