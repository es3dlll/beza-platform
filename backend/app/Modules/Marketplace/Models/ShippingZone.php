<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'name', 'name_ar', 'governorates', 'base_fee', 'per_kg_fee',
        'estimated_days', 'is_active',
    ];

    protected $casts = [
        'governorates' => 'json',
        'base_fee' => 'integer',
        'per_kg_fee' => 'integer',
        'estimated_days' => 'integer',
        'is_active' => 'boolean',
    ];
}
