<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Listeners;

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Remittance\Events\RemittanceApproved;
use App\Modules\Remittance\Events\RemittanceCompleted;
use App\Modules\Remittance\Events\RemittanceInitiated;

final class LogRemittanceActivity
{
    public function handle(RemittanceInitiated|RemittanceApproved|RemittanceCompleted $event): void
    {
        if ($event instanceof RemittanceInitiated) {
            AuditLog::create([
                'user_id' => $event->userId,
                'action' => 'remittance_initiated',
                'resource_type' => 'remittance',
                'resource_id' => $event->remittance->id,
                'metadata' => [
                    'from_currency' => $event->fromCurrency,
                    'to_currency' => $event->toCurrency,
                    'from_amount_fils' => $event->amount->fils(),
                    'reference' => $event->remittance->reference_number,
                ],
                'result' => 'pending',
            ]);
        }

        if ($event instanceof RemittanceApproved) {
            AuditLog::create([
                'user_id' => $event->userId,
                'action' => 'remittance_approved',
                'resource_type' => 'remittance',
                'resource_id' => $event->remittance->id,
                'metadata' => [
                    'from_amount_fils' => $event->amount->fils(),
                    'fee_fils' => $event->remittance->fee_fils,
                ],
                'result' => 'success',
            ]);
        }

        if ($event instanceof RemittanceCompleted) {
            AuditLog::create([
                'user_id' => $event->remittance->sender_user_id,
                'action' => 'remittance_completed',
                'resource_type' => 'remittance',
                'resource_id' => $event->remittance->id,
                'metadata' => [
                    'ledger_entry_id' => $event->ledgerEntry->id,
                    'to_amount_fils' => $event->remittance->to_amount_fils,
                    'status' => $event->status,
                ],
                'result' => 'success',
            ]);
        }
    }
}
