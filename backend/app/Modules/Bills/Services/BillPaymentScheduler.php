<?php

declare(strict_types=1);

namespace App\Modules\Bills\Services;

use App\Modules\Bills\Events\BillScheduled;
use App\Modules\Bills\Events\BillScheduleConfirmed;
use App\Modules\Bills\Models\ScheduledPayment;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\Fraud\Jobs\FraudDetectionEngine;
use App\Models\User;

final class BillPaymentScheduler
{
    public function createSchedule(User $user, array $data): ScheduledPayment
    {
        $schedule = ScheduledPayment::create([
            'user_id' => $user->id,
            'bill_provider_id' => $data['bill_provider_id'],
            'account_number' => $data['account_number'],
            'amount_fils' => $data['amount_fils'],
            'recurrence' => $data['recurrence'],
            'recurrence_day' => $data['recurrence_day'],
            'next_execution_date' => $data['next_execution_date'] ?? $this->calculateFirstDate($data['recurrence'], $data['recurrence_day']),
            'is_active' => true,
        ]);

        event(new BillScheduled($schedule));

        $agent = \App\Modules\Agent\Models\Agent::where('user_id', $user->id)->first();
        if ($agent) {
            FraudDetectionEngine::dispatch(
                agent: $agent,
                amountFils: $data['amount_fils'],
                currency: 'SYP',
                requestId: $schedule->id,
                region: $data['metadata']['region'] ?? null,
            );
        }

        event(new BillScheduleConfirmed($schedule));

        return $schedule->fresh();
    }

    public function toggleSchedule(string $id): ?ScheduledPayment
    {
        $schedule = ScheduledPayment::find($id);
        if (!$schedule) return null;
        $schedule->update(['is_active' => !$schedule->is_active]);
        return $schedule->fresh();
    }

    public function cancelSchedule(string $id): ?ScheduledPayment
    {
        $schedule = ScheduledPayment::find($id);
        if (!$schedule) return null;
        $schedule->update(['is_active' => false]);
        return $schedule->fresh();
    }

    public function getActiveSchedules(): array
    {
        return ScheduledPayment::active()->orderBy('next_execution_date')->get()->all();
    }

    public function getDueSchedules(): array
    {
        return ScheduledPayment::due()->orderBy('next_execution_date')->get()->all();
    }

    private function calculateFirstDate(string $recurrence, int $day): string
    {
        $now = now();
        return match ($recurrence) {
            'monthly' => $now->day(min($day, $now->daysInMonth))->toDateString(),
            'quarterly' => $now->addMonths(3)->day(min($day, $now->daysInMonth))->toDateString(),
            'yearly' => $now->addYear()->day(min($day, $now->daysInMonth))->toDateString(),
            default => $now->addMonth()->toDateString(),
        };
    }
}
