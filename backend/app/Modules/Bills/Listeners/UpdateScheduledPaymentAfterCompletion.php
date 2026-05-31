<?php

declare(strict_types=1);

namespace App\Modules\Bills\Listeners;

use App\Modules\Bills\Events\BillPaymentCompleted;
use App\Modules\Bills\Models\ScheduledPayment;

final class UpdateScheduledPaymentAfterCompletion
{
    public function handle(BillPaymentCompleted $event): void
    {
        $schedule = ScheduledPayment::where('user_id', $event->bill->user_id)
            ->where('bill_provider_id', $event->bill->bill_provider_id)
            ->where('account_number', $event->bill->account_number)
            ->where('is_active', true)
            ->first();

        if ($schedule && $schedule->next_execution_date?->isToday()) {
            $schedule->update([
                'last_executed_at' => now(),
                'next_execution_date' => $schedule->calculateNextDate(),
            ]);
        }
    }
}
