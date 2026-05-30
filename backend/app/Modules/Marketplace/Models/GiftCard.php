<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Modules\Marketplace\Enums\GiftCardStatus;

final class GiftCard extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'order_id', 'vendor_id', 'amount', 'balance', 'code', 'pin',
        'recipient_phone', 'message', 'status', 'delivery_method',
        'delivered_at', 'redeemed_at', 'expires_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance' => 'integer',
        'status' => GiftCardStatus::class,
        'delivered_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
