<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Marketplace\Enums\VendorStatus;

class Vendor extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'user_id', 'shop_name', 'shop_name_ar', 'description', 'phone',
        'governorate', 'commission_rate', 'status', 'is_invite_only',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_invite_only' => 'boolean',
        'status' => VendorStatus::class,
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'vendor_id', 'id');
    }
}
