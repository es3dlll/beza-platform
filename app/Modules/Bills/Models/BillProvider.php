<?php

declare(strict_types=1);

namespace Modules\Bills\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class BillProvider extends Model
{
    use HasUlids;

    protected $table = 'bill_providers';

    protected $fillable = [
        'id', 'code', 'name', 'name_ar', 'category', 'account_label',
        'account_format_regex', 'supported_account_types',
        'fee_percentage', 'fee_min_syp', 'fee_max_syp', 'is_active',
        'integration_config',
    ];

    protected function casts(): array
    {
        return [
            'supported_account_types' => 'array',
            'integration_config' => 'array',
            'is_active' => 'boolean',
            'fee_percentage' => 'decimal:2',
            'fee_min_syp' => 'integer',
            'fee_max_syp' => 'integer',
        ];
    }
}
