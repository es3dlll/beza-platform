<?php

declare(strict_types=1);

namespace App\Modules\FinancialCore\Models;

use App\Modules\FinancialCore\Database\Factories\TransactionFactory;
use App\Modules\Ledger\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Transaction extends Model
{
    use HasFactory;

    protected $table = 'financial_transactions';

    protected $fillable = [
        'type', 'status', 'wallet_id', 'from_account_id', 'to_account_id',
        'amount', 'currency', 'fee_amount', 'fee_account_id', 'fee_basis_points',
        'idempotency_key', 'description', 'description_ar', 'metadata',
        'reversed_by', 'reversal_of', 'journal_entry_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee_amount' => 'integer',
        'fee_basis_points' => 'integer',
        'metadata' => 'array',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(static function (self $model): void {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }
        });
    }

    public function reversal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of');
    }

    public function reversals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'reversal_of');
    }

    public function journalEntry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function scopeByStatus($query, string $status): void
    {
        $query->where('status', $status);
    }

    public function scopeByType($query, string $type): void
    {
        $query->where('type', $type);
    }

    public function scopeByWallet($query, string $walletId): void
    {
        $query->where('wallet_id', $walletId);
    }
}
