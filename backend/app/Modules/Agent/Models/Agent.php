<?php

declare(strict_types=1);

namespace App\Modules\Agent\Models;

use App\Modules\Agent\Database\Factories\AgentFactory;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'kyc_tier' => 't0',
        'status' => 'pending',
        'is_verified' => false,
    ];

    protected $fillable = [
        'user_id', 'phone', 'name', 'name_ar', 'kyc_tier', 'status',
        'id_type', 'id_number', 'is_verified', 'verified_at',
        'gps_lat', 'gps_lng', 'address', 'address_ar',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'gps_lat' => 'decimal:7',
        'gps_lng' => 'decimal:7',
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

    protected static function newFactory(): AgentFactory
    {
        return AgentFactory::new();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AgentWallet::class, 'agent_id');
    }

    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AgentWallet::class, 'agent_id');
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AgentTransaction::class, 'agent_id');
    }

    public function settlements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Settlement::class, 'agent_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canTransact(): bool
    {
        return in_array($this->status, ['active'], true) && $this->is_verified;
    }

    public function dailyLimitRemaining(): int
    {
        $wallet = $this->wallet;
        if ($wallet === null) {
            return 0;
        }
        return $wallet->daily_limit - $wallet->daily_used;
    }
}
