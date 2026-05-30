<?php

declare(strict_types=1);

namespace Modules\Fraud\Models;

use Illuminate\Database\Eloquent\Model;

class FraudRule extends Model
{
    protected $table = 'fraud_rules';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'rule_type', 'description', 'parameters', 'risk_score', 'is_active', 'severity',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'is_active' => 'boolean',
            'risk_score' => 'integer',
        ];
    }
}
