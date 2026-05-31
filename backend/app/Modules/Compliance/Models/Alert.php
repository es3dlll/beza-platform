<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Models;

use Illuminate\Database\Eloquent\Model;

final class Alert extends Model
{
    protected $table = 'compliance_alerts';

    protected $fillable = [
        'alert_id',
        'case_id',
        'severity',
        'message',
        'rule_id',
        'risk_score',
        'context',
        'status',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'context' => 'array',
        'risk_score' => 'integer',
        'resolved_at' => 'datetime',
    ];
}
