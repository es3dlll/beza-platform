<?php

declare(strict_types=1);

namespace Modules\Cards\Models;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $table = 'cards';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'card_type', 'status', 'cardholder_name',
        'card_number_last4', 'expiry_month', 'expiry_year', 'currency',
        'daily_limit', 'weekly_limit', 'monthly_limit',
        'daily_used', 'weekly_used', 'monthly_used', 'single_txn_limit',
        'is_virtual', 'international_enabled', 'atm_enabled',
        'contactless_enabled', 'ecommerce_enabled',
        'activated_at', 'suspended_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'daily_limit' => 'integer',
            'weekly_limit' => 'integer',
            'monthly_limit' => 'integer',
            'daily_used' => 'integer',
            'weekly_used' => 'integer',
            'monthly_used' => 'integer',
            'single_txn_limit' => 'integer',
            'is_virtual' => 'boolean',
            'international_enabled' => 'boolean',
            'atm_enabled' => 'boolean',
            'contactless_enabled' => 'boolean',
            'ecommerce_enabled' => 'boolean',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
