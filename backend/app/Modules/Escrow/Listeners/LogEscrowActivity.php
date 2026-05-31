<?php

declare(strict_types=1);

namespace App\Modules\Escrow\Listeners;

use App\Modules\AuditLog\Models\AuditLog;
use App\Modules\Escrow\Events\EscrowDisputed;
use App\Modules\Escrow\Events\EscrowFunded;
use App\Modules\Escrow\Events\EscrowInitiated;
use App\Modules\Escrow\Events\EscrowRefunded;
use App\Modules\Escrow\Events\EscrowReleased;

final class LogEscrowActivity
{
    public function handle(object $event): void
    {
        $action = match ($event::class) {
            EscrowInitiated::class => 'escrow_initiated',
            EscrowFunded::class => 'escrow_funded',
            EscrowReleased::class => 'escrow_released',
            EscrowRefunded::class => 'escrow_refunded',
            EscrowDisputed::class => 'escrow_disputed',
            default => null,
        };

        if ($action === null) return;

        $tx = $event->transaction ?? null;
        if (!$tx) return;

        $dispute = $event->dispute ?? null;

        AuditLog::create([
            'user_id' => $tx->buyer_id,
            'action' => $action,
            'resource_type' => 'escrow',
            'resource_id' => $tx->id,
            'result' => 'success',
            'metadata' => [
                'amount_fils' => $tx->amount_fils,
                'buyer_id' => $tx->buyer_id,
                'seller_id' => $tx->seller_id,
                'dispute_id' => $dispute?->id,
                'dispute_reason' => $dispute?->reason,
            ],
        ]);
    }
}
