<?php

declare(strict_types=1);

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

final class LoyaltyPoints extends Model
{
    protected $table = 'loyalty_points';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'balance', 'lifetime_earned', 'lifetime_redeemed', 'tier_level',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'lifetime_earned' => 'integer',
            'lifetime_redeemed' => 'integer',
        ];
    }
}
