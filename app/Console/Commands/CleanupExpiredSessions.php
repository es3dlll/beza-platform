<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Identity\Models\Session;

final class CleanupExpiredSessions extends Command
{
    protected $signature = 'cleanup:expired-sessions';
    protected $description = 'Remove expired sessions';

    public function handle(): int
    {
        $deleted = Session::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$deleted} expired sessions");
        return self::SUCCESS;
    }
}
