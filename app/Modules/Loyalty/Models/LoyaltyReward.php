<?php

declare(strict_types=1);

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyReward extends Model
{
    protected $table = 'loyalty_rewards';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'name_ar', 'type', 'points_cost', 'description',
        'description_ar', 'tier_requirement', 'discount_value', 'discount_type',
        'stock', 'image_url', 'is_active', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'discount_value' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
