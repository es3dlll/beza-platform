<?php

declare(strict_types=1);

namespace Modules\Cards\Models;

use Illuminate\Database\Eloquent\Model;

final class CardTransaction extends Model
{
    protected $table = 'card_transactions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'card_id', 'user_id', 'type', 'amount', 'currency',
        'status', 'merchant_name', 'merchant_category', 'merchant_country',
        'is_international', 'channel', 'decline_reason',
        'reference_id', 'authorized_at', 'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_international' => 'boolean',
            'authorized_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }
}
