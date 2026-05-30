<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Jobs;

use Modules\Ledger\Services\HoldService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ReleaseExpiredHoldsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(HoldService $holds): void
    {
        $released = $holds->releaseExpired();
        logger("CFE Released $released expired holds");
    }
}
