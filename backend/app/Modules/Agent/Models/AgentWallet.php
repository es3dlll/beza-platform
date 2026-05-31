<?php

declare(strict_types=1);

namespace App\Modules\Agent\Models;

use App\Modules\Agent\Database\Factories\AgentWalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class AgentWallet extends Model
{
    use HasFactory;

    protected $table = 'agent_wallets';

    protected $fillable = [
        'agent_id', 'currency', 'balance', 'float_balance',
        'daily_limit', 'daily_used', 'monthly_limit', 'monthly_used', 'status',
    ];

    protected $casts = [
        'balance' => 'integer',
        'float_balance' => 'integer',
        'daily_limit' => 'integer',
        'daily_used' => 'integer',
        'monthly_limit' => 'integer',
        'monthly_used' => 'integer',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(static function (self $model): void {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }
        });
    }

    protected static function newFactory(): AgentWalletFactory
    {
        return AgentWalletFactory::new();
    }

    public function agent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function withinDailyLimit(int $amount): bool
    {
        return ($this->daily_used + $amount) <= $this->daily_limit;
    }

    public function withinMonthlyLimit(int $amount): bool
    {
        return ($this->monthly_used + $amount) <= $this->monthly_limit;
    }

    public function hasSufficientFloat(int $amount): bool
    {
        return $this->float_balance >= $amount;
    }
}
