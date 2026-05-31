<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Models;

use Illuminate\Database\Eloquent\Model;

final class Merchant extends Model
{
    protected $fillable = [
        'merchant_id', 'business_name', 'owner_id', 'phone', 'category',
        'settlement_cycle', 'commission_bps', 'status', 'compliance_level',
    ];
}
