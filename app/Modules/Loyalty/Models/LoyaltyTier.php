<?php

declare(strict_types=1);

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    protected $table = 'loyalty_tiers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'name_ar', 'level', 'min_points',
        'points_multiplier', 'cashback_rate', 'benefits', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points_multiplier' => 'float',
            'cashback_rate' => 'float',
            'min_points' => 'integer',
            'benefits' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
