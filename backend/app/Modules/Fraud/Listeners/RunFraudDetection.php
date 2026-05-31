<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Listeners;

use App\Modules\Agent\Events\LiquidityRequested;
use App\Modules\Fraud\Jobs\FraudDetectionEngine;

final class RunFraudDetection
{
    public function handle(LiquidityRequested $event): void
    {
        FraudDetectionEngine::dispatch(
            agent: $event->agent,
            amountFils: $event->amount->fils(),
            currency: $event->amount->currency()->value,
            requestId: $event->requestId ?? bin2hex(random_bytes(8)),
            region: $event->agent->region,
            deviceId: request()->header('X-Device-Id'),
        );
    }
}
