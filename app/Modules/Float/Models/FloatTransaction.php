<?php

declare(strict_types=1);

namespace Modules\Float\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FloatTransaction extends Model
{
    protected $table = 'float_transactions';

    protected $fillable = [
        'id', 'float_account_id', 'type', 'amount', 'balance_before',
        'balance_after', 'reference_type', 'reference_id',
        'description', 'status',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function account(): BelongsTo
    {
        return $this->belongsTo(FloatAccount::class, 'float_account_id');
    }
}
