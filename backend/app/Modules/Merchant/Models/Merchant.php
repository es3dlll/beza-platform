<?php

declare(strict_types=1);

namespace Modules\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Modules\Identity\Models\User;

final class Merchant extends Model
{
    use HasUlids;

    protected $table = 'merchants';

    protected $fillable = [
        'user_id', 'business_name', 'business_name_ar', 'commercial_registration',
        'tax_number', 'phone', 'email', 'governorate', 'city', 'address',
        'category', 'status', 'monthly_volume_syp', 'mdr_percentage',
        'mdr_min_syp', 'mdr_max_syp', 'max_txn_amount', 'is_micro_merchant',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'monthly_volume_syp' => 'integer',
            'mdr_percentage' => 'decimal:2',
            'mdr_min_syp' => 'integer',
            'mdr_max_syp' => 'integer',
            'max_txn_amount' => 'integer',
            'is_micro_merchant' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stores()
    {
        return $this->hasMany(MerchantStore::class);
    }
}
