<?php

declare(strict_types=1);

namespace Modules\Remittance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

final class Corridor extends Model
{
    use HasUlids;

    protected $table = 'corridors';

    protected $fillable = [
        'name',
        'source_country',
        'source_currency',
        'target_currency',
        'fx_rate_source',
        'fixed_spread_pct',
        'fee_type',
        'fee_structure',
        'min_amount',
        'max_amount',
        'daily_limit_per_sender',
        'monthly_limit_per_sender',
        'is_active',
        'supported_payout_methods',
        'compliance_requirements',
        'partner_name',
    ];

    protected function casts(): array
    {
        return [
            'fee_structure' => 'array',
            'supported_payout_methods' => 'array',
            'compliance_requirements' => 'array',
            'is_active' => 'boolean',
            'min_amount' => 'integer',
            'max_amount' => 'integer',
            'daily_limit_per_sender' => 'integer',
            'monthly_limit_per_sender' => 'integer',
        ];
    }
}
