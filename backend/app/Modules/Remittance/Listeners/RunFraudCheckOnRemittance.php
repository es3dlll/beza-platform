<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Listeners;

use App\Modules\Remittance\Events\RemittanceInitiated;
use App\Modules\Fraud\Jobs\FraudDetectionEngine;

final class RunFraudCheckOnRemittance
{
    public function handle(RemittanceInitiated $event): void
    {
        $agent = $event->remittance->sender_user_id
            ? \App\Modules\Agent\Models\Agent::where('user_id', $event->remittance->sender_user_id)->first()
            : null;

        if ($agent) {
            FraudDetectionEngine::dispatch(
                agent: $agent,
                amountFils: $event->amount->fils(),
                currency: $event->fromCurrency,
                requestId: $event->remittance->id,
                region: $event->remittance->metadata['region'] ?? null,
            );
        }
    }
}
