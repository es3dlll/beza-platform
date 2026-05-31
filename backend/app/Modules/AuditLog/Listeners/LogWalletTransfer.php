<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Listeners;

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Ledger\Events\TransferCompleted;

final class LogWalletTransfer
{
    public function handle(TransferCompleted $event): void
    {
        AuditLog::create([
            'user_id' => $event->fromUserId,
            'action' => 'wallet_transfer',
            'resource_type' => 'ledger_entry',
            'resource_id' => $event->entry->id,
            'metadata' => [
                'amount_fils' => $event->amount->fils(),
                'currency' => $event->amount->currency()->value,
                'from_wallet_id' => $event->entry->debit_wallet_id,
                'to_wallet_id' => $event->entry->credit_wallet_id,
                'to_user_id' => $event->toUserId,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'result' => 'success',
        ]);
    }
}
