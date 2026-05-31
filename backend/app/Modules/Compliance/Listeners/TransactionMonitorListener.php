<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Listeners;

use App\Modules\Compliance\Events\TransactionCompleted;
use App\Modules\Compliance\Services\FraudDetectionEngine;
use Illuminate\Support\Facades\Log;

final readonly class TransactionMonitorListener
{
    public function __construct(private FraudDetectionEngine $engine) {}

    public function handle(TransactionCompleted $event): void
    {
        Log::info('TransactionMonitorListener: evaluating transaction', [
            'transaction_id' => $event->transactionId,
        ]);

        $this->engine->evaluateTransaction($event);
    }
}
