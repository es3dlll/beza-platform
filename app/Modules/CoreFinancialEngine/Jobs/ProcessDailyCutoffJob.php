<?php

namespace Modules\CoreFinancialEngine\Jobs;

use Modules\CoreFinancialEngine\Services\SettlementEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessDailyCutoffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $date,
    ) {}

    public function handle(SettlementEngine $settlement): void
    {
        $summary = $settlement->dailyCutoff($this->date);
        logger('Daily cutoff completed', $summary);
    }
}
