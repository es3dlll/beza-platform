<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Models;

use Illuminate\Database\Eloquent\Model;

final class ComplianceCase extends Model
{
    protected $table = 'compliance_cases';

    protected $fillable = [
        'case_id',
        'transaction_id',
        'account_id',
        'risk_score',
        'status',
        'severity',
        'triggered_rules',
        'context',
        'reviewer_id',
        'reviewed_at',
        'resolution',
        'resolution_reason',
        'escalated_at',
        'closed_at',
    ];

    protected $casts = [
        'triggered_rules' => 'array',
        'context' => 'array',
        'risk_score' => 'integer',
    ];
}
