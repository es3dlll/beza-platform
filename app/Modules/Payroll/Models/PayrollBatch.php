<?php

declare(strict_types=1);

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollBatch extends Model
{
    protected $table = 'payroll_batches';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'employer_id', 'batch_reference', 'total_employees', 'total_amount',
        'currency', 'status', 'period_month', 'notes', 'approved_by', 'approved_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'total_employees' => 'integer',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
