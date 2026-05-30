<?php

declare(strict_types=1);

namespace Modules\Savings\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
{
    protected $table = 'savings_transactions';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'savings_account_id', 'savings_goal_id', 'user_id', 'type', 'amount',
        'balance_before', 'balance_after', 'currency', 'description',
        'reference_type', 'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
        ];
    }
}
