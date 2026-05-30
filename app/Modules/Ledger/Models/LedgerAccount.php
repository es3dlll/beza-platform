<?php
declare(strict_types=1);

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LedgerAccount extends Model
{
    protected $table = 'ledger_accounts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'code', 'account_number', 'name', 'name_ar', 'type', 'category',
        'currency', 'balance', 'available_balance', 'balance_usd',
        'owner_type', 'owner_id', 'parent_id', 'metadata',
        'is_active', 'is_system', 'description', 'module',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'balance' => 'integer',
        'available_balance' => 'integer',
        'balance_usd' => 'integer',
        'metadata' => 'array',
    ];

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function holds(): HasMany
    {
        return $this->hasMany(LedgerHold::class, 'account_id');
    }

    public function reserves(): HasMany
    {
        return $this->hasMany(LedgerReserve::class, 'account_id');
    }

    public function credit(int $amount): void
    {
        if (in_array($this->type, ['liability', 'income', 'equity'])) {
            $this->increment('balance', $amount);
        } else {
            $this->decrement('balance', $amount);
        }
    }

    public function debit(int $amount): void
    {
        if (in_array($this->type, ['asset', 'expense'])) {
            $this->increment('balance', $amount);
        } else {
            $this->decrement('balance', $amount);
        }
    }

    public function getBalanceAttribute(): int
    {
        return (int) $this->attributes['balance'];
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->id ??= (string) Str::ulid();
        });
    }
}
