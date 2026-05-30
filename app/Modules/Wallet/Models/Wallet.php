<?php

declare(strict_types=1);

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Models\User;
use Modules\Wallet\Services\WalletStateMachine;

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

    public function activate(?string $reason = null): Wallet
    {
        return app(WalletStateMachine::class)->transition($this, 'activate', $reason);
    }

    public function suspend(?string $reason = null): Wallet
    {
        return app(WalletStateMachine::class)->transition($this, 'suspend', $reason);
    }

    public function limit(?string $reason = null): Wallet
    {
        return app(WalletStateMachine::class)->transition($this, 'limit', $reason);
    }

    public function freeze(?string $reason = null): Wallet
    {
        return app(WalletStateMachine::class)->transition($this, 'freeze', $reason);
    }

    public function close(?string $reason = null): Wallet
    {
        return app(WalletStateMachine::class)->transition($this, 'close', $reason);
    }

    public function allowedActions(): array
    {
        return WalletStateMachine::allowedActions($this->status);
    }

    public function assertOperational(): void
    {
        if ($this->status === 'frozen') {
            throw new \Modules\Wallet\Exceptions\WalletFrozenException($this->id);
        }
        if ($this->status === 'closed') {
            throw new \RuntimeException("Wallet {$this->id} is closed");
        }
    }
}
