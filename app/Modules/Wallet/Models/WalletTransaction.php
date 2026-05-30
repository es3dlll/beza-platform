<?php

declare(strict_types=1);

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WalletTransaction extends Model
{
    protected $table = 'wallet_transactions';

    protected $fillable = [
        'id', 'wallet_id', 'type', 'amount', 'currency',
        'balance_before', 'balance_after', 'reference_type', 'reference_id',
        'cfe_transaction_id', 'status', 'description',
        'related_wallet_id', 'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'json',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}
