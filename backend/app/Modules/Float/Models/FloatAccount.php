<?php

declare(strict_types=1);

namespace Modules\Float\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FloatAccount extends Model
{
    protected $table = 'float_accounts';

    protected $fillable = [
        'id', 'owner_type', 'owner_id', 'float_type', 'balance',
        'pending_balance', 'currency', 'status', 'minimum_balance',
        'maximum_balance', 'metadata',
    ];

    protected $casts = [
        'balance' => 'integer',
        'pending_balance' => 'integer',
        'minimum_balance' => 'integer',
        'maximum_balance' => 'integer',
        'metadata' => 'json',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function transactions(): HasMany
    {
        return $this->hasMany(FloatTransaction::class, 'float_account_id');
    }

    public function availableBalance(): int
    {
        return $this->balance - $this->pending_balance;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function withinLimits(int $amount): bool
    {
        if ($this->maximum_balance && ($this->balance + $amount) > $this->maximum_balance) {
            return false;
        }
        if (($this->balance - $amount) < $this->minimum_balance) {
            return false;
        }
        return true;
    }
}
