<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Listeners;

use App\Modules\Escrow\Events\EscrowInitiated;
use App\Modules\Fraud\Jobs\FraudDetectionEngine;

final class RunFraudCheckOnEscrow
{
    public function handle(EscrowInitiated $event): void
    {
        $agent = $event->userId
            ? \App\Modules\Agent\Models\Agent::where('user_id', $event->userId)->first()
            : null;

        if ($agent) {
            FraudDetectionEngine::dispatch(
                agent: $agent,
                amountFils: $event->amount->fils(),
                currency: 'SYP',
                requestId: $event->transaction->id,
                region: $event->transaction->metadata['region'] ?? null,
            );
        }
    }
}
