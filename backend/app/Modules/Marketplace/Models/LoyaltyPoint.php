<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class LoyaltyPoint extends Model
{
    use HasUlids;

    protected $table = 'loyalty_ledger';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id', 'user_id', 'points', 'action', 'reference_type', 'reference_id', 'created_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'created_at' => 'datetime',
    ];
}
