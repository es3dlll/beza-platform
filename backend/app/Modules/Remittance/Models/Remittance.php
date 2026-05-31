<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Models;

use App\Modules\Remittance\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;

final class Remittance extends Model
{
    protected $fillable = [
        'remittance_id', 'idempotency_key', 'sender_id', 'recipient_name',
        'recipient_phone', 'recipient_country', 'from_currency', 'to_currency',
        'source_amount', 'destination_amount', 'buy_rate', 'spread_bps',
        'fee_amount', 'total_charge', 'status', 'compliance_tier',
        'expires_at', 'completed_at', 'cancellation_reason', 'audit_trail',
    ];

    protected $casts = [
        'audit_trail' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            TransferStatus::SETTLED,
            TransferStatus::REJECTED,
            TransferStatus::CANCELLED,
            TransferStatus::EXPIRED,
        ], true);
    }
}
