<?php

declare(strict_types=1);

namespace App\Modules\Bills\Listeners;

use App\Modules\Bills\Events\BillPaymentInitiated;
use App\Modules\Fraud\Jobs\FraudDetectionEngine;

final class RunFraudCheckOnBillPayment
{
    public function handle(BillPaymentInitiated $event): void
    {
        $agent = $event->userId
            ? \App\Modules\Agent\Models\Agent::where('user_id', $event->userId)->first()
            : null;

        if ($agent) {
            FraudDetectionEngine::dispatch(
                agent: $agent,
                amountFils: $event->amount->fils(),
                currency: 'SYP',
                requestId: $event->bill->id,
                region: $event->bill->metadata['region'] ?? null,
            );
        }
    }
}
