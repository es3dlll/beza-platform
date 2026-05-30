<?php

declare(strict_types=1);

namespace Modules\Fraud\Models;

use Illuminate\Database\Eloquent\Model;

final class FraudCase extends Model
{
    protected $table = 'fraud_cases';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'fraud_event_id', 'actor_id', 'actor_type', 'status', 'severity',
        'risk_score', 'description', 'evidence', 'reviewed_by', 'review_notes', 'decision', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'risk_score' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }
}
