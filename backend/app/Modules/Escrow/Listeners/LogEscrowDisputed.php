<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Listeners;

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Escrow\Events\EscrowDisputed;

final class LogEscrowDisputed
{
    public function handle(EscrowDisputed $event): void
    {
        AuditLog::create([
            'user_id' => $event->transaction->buyer_id,
            'action' => 'escrow_disputed',
            'resource_type' => 'escrow',
            'resource_id' => $event->transaction->id,
            'result' => 'success',
            'metadata' => [
                'amount_fils' => $event->transaction->amount_fils,
                'buyer_id' => $event->transaction->buyer_id,
                'seller_id' => $event->transaction->seller_id,
                'dispute_id' => $event->dispute->id,
                'dispute_reason' => $event->dispute->reason,
            ],
        ]);
    }
}
