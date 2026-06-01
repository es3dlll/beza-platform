<?php

namespace App\Modules\Wallet\Listeners;

use App\Models\AuditLog;
use App\Modules\Wallet\Events\TransferCompleted;
use Illuminate\Support\Facades\Request;

class LogTransferAudit
{
    public function handle(TransferCompleted $event): void
    {
        $transaction = $event->transaction;
        $senderWallet = $transaction->senderWallet;

        AuditLog::create([
            'user_id' => $senderWallet->user_id,
            'method' => 'POST',
            'path' => '/api/v1/transfer',
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'fingerprint' => $transaction->idempotency_key,
        ]);
    }
}
