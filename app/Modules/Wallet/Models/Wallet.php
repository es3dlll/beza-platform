<?php

declare(strict_types=1);

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Models\User;

final class Wallet extends Model
{
    protected $table = 'wallets';

    protected $fillable = [
        'id', 'user_id', 'currency', 'balance', 'available_balance',
        'status', 'kyc_tier_required', 'daily_limit', 'daily_used',
        'daily_reset_at', 'metadata', 'ledger_account_id',
    ];

    protected $casts = [
        'balance' => 'integer',
        'available_balance' => 'integer',
        'daily_limit' => 'integer',
        'daily_used' => 'integer',
        'daily_reset_at' => 'datetime',
        'metadata' => 'json',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasSufficientBalance(int $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    public function withinDailyLimit(int $amount): bool
    {
        $this->resetDailyIfNeeded();
        return ($this->daily_used + $amount) <= $this->daily_limit;
    }

    public function resetDailyIfNeeded(): void
    {
        if ($this->daily_reset_at === null || $this->daily_reset_at->isPast()) {
            $this->daily_used = 0;
            $this->daily_reset_at = now()->endOfDay();
            $this->save();
        }
    }
}
