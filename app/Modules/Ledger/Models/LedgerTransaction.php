<?php
declare(strict_types=1);

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class LedgerTransaction extends Model
{
    protected $table = 'ledger_transactions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'journal_entry_id', 'transactionable_type', 'transactionable_id',
        'type', 'amount', 'currency', 'status', 'idempotency_key',
        'description', 'created_by', 'completed_at', 'failed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function hold(): HasOne
    {
        return $this->hasOne(LedgerHold::class, 'transaction_id');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByIdempotencyKey(Builder $query, string $key): Builder
    {
        return $query->where('idempotency_key', $key);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->id ??= (string) Str::ulid();
        });
    }
}
