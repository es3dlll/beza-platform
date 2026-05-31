<?php

declare(strict_types=1);

namespace App\Modules\Bills\Listeners;

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Bills\Events\BillPaymentCompleted;
use App\Modules\Bills\Events\BillPaymentFailed;
use App\Modules\Bills\Events\BillPaymentInitiated;
use App\Modules\Bills\Events\BillScheduleConfirmed;
use App\Modules\Bills\Events\BillScheduled;

final class LogBillActivity
{
    public function handle(
        BillPaymentInitiated|BillPaymentCompleted|BillPaymentFailed|BillScheduled|BillScheduleConfirmed $event
    ): void {
        $eventClass = $event::class;
        $action = match ($eventClass) {
            BillPaymentInitiated::class => 'bill_payment_initiated',
            BillPaymentCompleted::class => 'bill_payment_completed',
            BillPaymentFailed::class => 'bill_payment_failed',
            BillScheduled::class => 'bill_schedule_created',
            BillScheduleConfirmed::class => 'bill_schedule_confirmed',
            default => 'bill_activity',
        };

        $bill = $event->bill ?? null;
        $schedule = $event->schedule ?? null;

        $succeeded = !($event instanceof BillPaymentFailed);

        AuditLog::create([
            'user_id' => $bill?->user_id ?? $schedule?->user_id ?? 'system',
            'action' => $action,
            'resource_type' => 'bill',
            'resource_id' => $bill?->id ?? $schedule?->id,
            'result' => $succeeded ? 'success' : 'failed',
            'metadata' => [
                'amount_fils' => $bill?->amount_fils ?? $schedule?->amount_fils,
                'provider_id' => $bill?->bill_provider_id ?? $schedule?->bill_provider_id,
                'receipt' => $event->receiptReference ?? null,
                'reason' => $event->reason ?? null,
            ],
        ]);
    }
}
