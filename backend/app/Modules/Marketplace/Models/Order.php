<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Marketplace\Enums\OrderStatus;

class Order extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'user_id', 'vendor_id', 'order_number', 'total_amount', 'fee_amount',
        'net_amount', 'currency', 'status', 'notes', 'placed_at', 'completed_at', 'cancelled_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'fee_amount' => 'integer',
        'net_amount' => 'integer',
        'status' => OrderStatus::class,
        'placed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(Fulfillment::class, 'order_id', 'id');
    }
}
