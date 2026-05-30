<?php

declare(strict_types=1);

namespace Modules\Savings\Models;

use Illuminate\Database\Eloquent\Model;

final class SavingsAccount extends Model
{
    protected $table = 'savings_accounts';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'savings_goal_id', 'balance', 'total_contributions',
        'total_profit', 'total_withdrawn', 'currency', 'status', 'last_contribution_at',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'total_contributions' => 'integer',
            'total_profit' => 'integer',
            'total_withdrawn' => 'integer',
            'last_contribution_at' => 'datetime',
        ];
    }
}
