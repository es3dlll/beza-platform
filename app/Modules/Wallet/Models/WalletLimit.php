<?php

declare(strict_types=1);

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;

final class WalletLimit extends Model
{
    protected $table = 'wallet_limits';

    protected $fillable = [
        'id', 'name', 'description', 'kyc_tier', 'limit_type',
        'period', 'max_amount', 'max_count', 'currency', 'is_active',
    ];

    protected $casts = [
        'max_amount' => 'integer',
        'max_count' => 'integer',
        'kyc_tier' => 'integer',
        'is_active' => 'boolean',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
