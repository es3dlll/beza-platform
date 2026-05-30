<?php

declare(strict_types=1);

namespace Modules\Settlement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SettlementLine extends Model
{
    protected $table = 'settlement_lines';

    protected $fillable = [
        'id', 'settlement_id', 'account_id', 'amount', 'type', 'description',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'settlement_id');
    }
}
