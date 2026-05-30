<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Ledger\Models\LedgerHold;

final class CleanupExpiredHolds extends Command
{
    protected $signature = 'cleanup:expired-holds';
    protected $description = 'Release expired ledger holds';

    public function handle(): int
    {
        $released = LedgerHold::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'released', 'released_at' => now(), 'release_reason' => 'auto_expired']);

        $this->info("Released {$released} expired holds");
        return self::SUCCESS;
    }
}
