<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Marketplace\Enums\ShipmentStatus;

class Shipment extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'order_id', 'carrier', 'tracking_number', 'status',
        'shipping_address', 'governorate', 'recipient_name', 'recipient_phone',
        'notes', 'shipped_at', 'delivered_at',
    ];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function codCollections(): HasMany
    {
        return $this->hasMany(CodCollection::class, 'shipment_id', 'id');
    }
}
