<?php

declare(strict_types=1);

namespace Modules\Fraud\Models;

use Illuminate\Database\Eloquent\Model;

final class FraudBlacklistEntry extends Model
{
    protected $table = 'fraud_blacklist';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'type', 'value', 'reason', 'source', 'added_by', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
