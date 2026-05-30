<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Models;

use Illuminate\Database\Eloquent\Model;

final class FeeRule extends Model
{
    protected $table = 'fee_rules';

    protected $fillable = [
        'id',
        'fee_type',
        'calculation_type',
        'value',
        'currency',
        'fee_account_number',
        'max_cap',
        'min_amount',
        'waived_for_tier',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'value' => 'integer',
        'max_cap' => 'integer',
        'min_amount' => 'integer',
        'waived_for_tier' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
