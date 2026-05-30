<?php

declare(strict_types=1);

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPointsTransaction extends Model
{
    protected $table = 'loyalty_points_transactions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'loyalty_points_id', 'type', 'points',
        'balance_before', 'balance_after', 'reference_type', 'reference_id',
        'description', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
