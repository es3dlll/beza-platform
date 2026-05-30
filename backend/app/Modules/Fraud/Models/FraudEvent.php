<?php

declare(strict_types=1);

namespace Modules\Fraud\Models;

use Illuminate\Database\Eloquent\Model;

final class FraudEvent extends Model
{
    protected $table = 'fraud_events';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'event_type', 'actor_id', 'actor_type', 'ip_address', 'device_id', 'user_agent',
        'latitude', 'longitude', 'metadata', 'risk_score', 'decision', 'matched_rule_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'risk_score' => 'integer',
        ];
    }
}
