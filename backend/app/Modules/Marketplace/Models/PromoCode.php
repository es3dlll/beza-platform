<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class PromoCode extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'code', 'discount_type', 'discount_value', 'min_order_amount',
        'max_uses', 'used_count', 'is_active', 'starts_at', 'expires_at',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'min_order_amount' => 'integer',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
