<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\FX\Models\FxQuote;

final class FxCleanExpiredQuotes extends Command
{
    protected $signature = 'fx:clean-expired-quotes';
    protected $description = 'Mark expired FX quotes as expired';

    public function handle(): int
    {
        $updated = FxQuote::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$updated} FX quotes");
        return self::SUCCESS;
    }
}
