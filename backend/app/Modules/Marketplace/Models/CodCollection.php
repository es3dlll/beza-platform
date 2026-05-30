<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Marketplace\Enums\CodStatus;

class CodCollection extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'shipment_id', 'order_id', 'amount', 'agent_id', 'status',
        'collected_at', 'remitted_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'status' => CodStatus::class,
        'collected_at' => 'datetime',
        'remitted_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
