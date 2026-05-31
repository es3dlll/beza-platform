<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Models;

use App\Modules\Fraud\Database\Factories\ComplianceRuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ComplianceRule extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'compliance_rules';

    protected $fillable = [
        'name',
        'key',
        'description',
        'rule_type',
        'parameters',
        'is_active',
        'priority',
        'risk_score_impact',
        'decision',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'risk_score_impact' => 'integer',
    ];
}
