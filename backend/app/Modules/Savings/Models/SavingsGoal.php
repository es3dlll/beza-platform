<?php

declare(strict_types=1);

namespace Modules\Savings\Models;

use Illuminate\Database\Eloquent\Model;

final class SavingsGoal extends Model
{
    protected $table = 'savings_goals';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'name', 'name_ar', 'target_amount', 'current_amount',
        'currency', 'status', 'target_date', 'category', 'icon', 'color',
        'auto_sweep_enabled', 'auto_sweep_amount', 'auto_sweep_frequency', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'integer',
            'current_amount' => 'integer',
            'target_date' => 'date',
            'auto_sweep_enabled' => 'boolean',
            'auto_sweep_amount' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function progressPercent(): float
    {
        if ($this->target_amount <= 0) return 0;
        return round(($this->current_amount / $this->target_amount) * 100, 2);
    }
}
