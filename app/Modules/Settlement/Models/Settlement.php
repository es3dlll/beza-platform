<?php

declare(strict_types=1);

namespace Modules\Settlement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Settlement extends Model
{
    protected $table = 'settlements';

    protected $fillable = [
        'id', 'reference_type', 'reference_id', 'settlement_type',
        'status', 'gross_amount', 'fee_amount', 'commission_amount',
        'net_amount', 'currency', 'settlement_account_id',
        'cfe_transaction_id', 'period_start', 'period_end',
        'settled_at', 'metadata',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'fee_amount' => 'integer',
        'commission_amount' => 'integer',
        'net_amount' => 'integer',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'settled_at' => 'datetime',
        'metadata' => 'json',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function lines(): HasMany
    {
        return $this->hasMany(SettlementLine::class, 'settlement_id');
    }
}
