<?php

declare(strict_types=1);

namespace Modules\FX\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FxConversion extends Model
{
    protected $table = 'fx_conversions';

    protected $fillable = [
        'id', 'quote_id', 'from_wallet_id', 'to_wallet_id',
        'from_currency', 'to_currency',
        'from_amount', 'to_amount', 'rate_applied',
        'fee_amount', 'fee_currency',
        'status', 'completed_at', 'failed_reason',
    ];

    protected $casts = [
        'rate_applied' => 'float',
        'from_amount' => 'integer',
        'to_amount' => 'integer',
        'fee_amount' => 'integer',
        'completed_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function quote(): BelongsTo
    {
        return $this->belongsTo(FxQuote::class, 'quote_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
