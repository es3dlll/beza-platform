<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Models;

use App\Modules\Ledger\Database\Factories\LedgerAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class LedgerAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): LedgerAccountFactory
    {
        return LedgerAccountFactory::new();
    }

    protected $table = 'ledger_accounts';

    protected $fillable = [
        'id',
        'code',
        'name',
        'name_ar',
        'type',
        'balance',
        'currency',
        'is_system',
        'metadata',
    ];

    protected $casts = [
        'balance' => 'integer',
        'is_system' => 'boolean',
        'metadata' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function debitLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id')->where('type', 'debit');
    }

    public function creditLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id')->where('type', 'credit');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
