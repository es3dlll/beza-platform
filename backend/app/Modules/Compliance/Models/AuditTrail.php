<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Models;

use Illuminate\Database\Eloquent\Model;

final class AuditTrail extends Model
{
    protected $table = 'compliance_audit_trails';

    protected $fillable = [
        'trace_id',
        'rule_id',
        'risk_score',
        'context',
        'action',
        'timestamp',
        'irreversible',
    ];

    protected $casts = [
        'context' => 'array',
        'risk_score' => 'integer',
        'timestamp' => 'integer',
        'irreversible' => 'boolean',
    ];
}
