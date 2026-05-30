<?php

declare(strict_types=1);

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

final class PayrollDisbursement extends Model
{
    protected $table = 'payroll_disbursements';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'payroll_batch_id', 'employer_id', 'employee_record_id',
        'employee_name', 'employee_phone', 'amount', 'currency',
        'status', 'wallet_transaction_id', 'failure_reason', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }
}
