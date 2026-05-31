<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Models;

use Illuminate\Database\Eloquent\Model;

final class ComplianceRuleConfig extends Model
{
    protected $table = 'compliance_rule_configs';

    protected $fillable = [
        'rule_id',
        'description',
        'evaluation_type',
        'threshold',
        'action',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
