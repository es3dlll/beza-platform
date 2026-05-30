<?php

declare(strict_types=1);

namespace Modules\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class MerchantStore extends Model
{
    use HasUlids;

    protected $table = 'merchant_stores';

    protected $fillable = [
        'merchant_id', 'name', 'name_ar', 'phone', 'governorate', 'city',
        'address', 'latitude', 'longitude', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
