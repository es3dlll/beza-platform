<?php

declare(strict_types=1);

namespace App\Modules\Fx\Models;

use App\Modules\FinancialCore\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class FxTransaction extends Model
{
    protected $table = 'fx_transactions';

    protected $fillable = [
        'wallet_id', 'type', 'status', 'base_currency', 'quote_currency',
        'debit_amount', 'credit_amount', 'rate_used', 'spread_bps_applied',
        'rate_source_id', 'fx_hold_id', 'cfe_transaction_id',
        'reversal_of', 'idempotency_key', 'description', 'description_ar',
    ];
    protected $casts = [
        'debit_amount' => 'integer',
        'credit_amount' => 'integer',
        'rate_used' => 'integer',
        'spread_bps_applied' => 'integer',
    ];
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(static function (self $model): void {
            if (empty($model->id)) {
                $model->id = Str::ulid()->toBase32();
            }
        });
    }

    public function cfeTransaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'cfe_transaction_id');
    }

    public function fxHold(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FxHold::class, 'fx_hold_id');
    }
}
