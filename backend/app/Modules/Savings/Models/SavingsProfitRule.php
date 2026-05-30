<?php

declare(strict_types=1);

namespace Modules\Savings\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsProfitRule extends Model
{
    protected $table = 'savings_profit_rules';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'annual_rate', 'calculation_method', 'distribution_method',
        'min_balance', 'min_duration_days', 'early_withdrawal_penalty_rate', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'annual_rate' => 'float',
            'early_withdrawal_penalty_rate' => 'float',
            'min_balance' => 'integer',
            'min_duration_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
