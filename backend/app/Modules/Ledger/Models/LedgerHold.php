<?php
declare(strict_types=1);

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class LedgerHold extends Model
{
    protected $table = 'ledger_holds';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'account_id', 'transaction_id', 'amount', 'currency',
        'reason', 'reference_type', 'reference_id', 'expires_at',
        'status', 'released_at', 'release_reason',
    ];

    protected $casts = [
        'amount' => 'integer',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class, 'transaction_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->id ??= (string) Str::ulid();
        });
    }
}
