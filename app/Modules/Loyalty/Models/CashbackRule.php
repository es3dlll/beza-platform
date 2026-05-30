<?php

declare(strict_types=1);

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class CashbackRule extends Model
{
    protected $table = 'cashback_rules';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'trigger_type', 'trigger_value', 'rate',
        'min_amount', 'max_cashback', 'currency', 'tier_requirement', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'min_amount' => 'integer',
            'max_cashback' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
