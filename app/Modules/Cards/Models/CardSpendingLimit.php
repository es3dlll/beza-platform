<?php

declare(strict_types=1);

namespace Modules\Cards\Models;

use Illuminate\Database\Eloquent\Model;

class CardSpendingLimit extends Model
{
    protected $table = 'card_spending_limits';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'card_id', 'limit_type', 'max_amount', 'currency', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
