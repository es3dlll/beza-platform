<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Identity\Models\OtpCode;

final class CleanupExpiredOtps extends Command
{
    protected $signature = 'cleanup:expired-otps';
    protected $description = 'Remove expired OTP codes';

    public function handle(): int
    {
        $deleted = OtpCode::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$deleted} expired OTP codes");
        return self::SUCCESS;
    }
}
