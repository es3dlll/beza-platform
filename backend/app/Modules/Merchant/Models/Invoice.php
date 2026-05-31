<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Models;

use App\Modules\Merchant\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;

final class Invoice extends Model
{
    protected $fillable = [
        'invoice_id', 'merchant_id', 'amount', 'tax_amount', 'total_amount',
        'description', 'category', 'status', 'settlement_status',
        'qr_token', 'qr_expires_at', 'cancellation_reason', 'paid_at',
    ];

    protected $casts = [
        'qr_expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            InvoiceStatus::PAID,
            InvoiceStatus::EXPIRED,
            InvoiceStatus::CANCELLED,
            InvoiceStatus::REFUNDED,
        ], true);
    }
}
