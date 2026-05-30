<?php

declare(strict_types=1);

namespace Modules\Agent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Agent extends Model
{
    protected $table = 'agents';

    protected $fillable = [
        'id', 'user_id', 'business_name', 'trade_license', 'agent_type',
        'status', 'governorate', 'city', 'area', 'address',
        'latitude', 'longitude',
        'daily_cash_in_limit', 'daily_cash_out_limit',
        'max_commission_per_txn', 'commission_rate',
        'wallet_id', 'phone', 'alt_phone', 'metadata',
        'approved_at', 'approved_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'daily_cash_in_limit' => 'integer',
        'daily_cash_out_limit' => 'integer',
        'max_commission_per_txn' => 'integer',
        'commission_rate' => 'float',
        'approved_at' => 'datetime',
        'metadata' => 'json',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function transactions(): HasMany
    {
        return $this->hasMany(AgentTransaction::class, 'agent_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['approved', 'active'], true);
    }
}
