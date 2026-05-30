<?php
declare(strict_types=1);

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class JournalLine extends Model
{
    protected $table = 'journal_lines';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'journal_entry_id', 'account_id', 'type', 'amount',
        'currency', 'description', 'description_ar',
        'reconciled', 'reconciled_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function scopeUnreconciled(Builder $query): Builder
    {
        return $query->where('reconciled', false);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->id ??= (string) Str::ulid();
        });
    }
}
