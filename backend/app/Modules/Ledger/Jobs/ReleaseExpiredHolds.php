<?php
declare(strict_types=1);

namespace Modules\Ledger\Jobs;

use Modules\Ledger\Services\HoldService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

final class ReleaseExpiredHolds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(HoldService $holds): void
    {
        $released = $holds->releaseExpired();
        logger("Released $released expired holds");
    }
}
