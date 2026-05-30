<?php

declare(strict_types=1);

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

final class Employer extends Model
{
    protected $table = 'employers';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'company_name', 'company_name_ar', 'commercial_registration',
        'tax_number', 'phone', 'email', 'governorate', 'city', 'address',
        'status', 'monthly_payroll_limit', 'used_monthly_payroll', 'employee_count',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'monthly_payroll_limit' => 'integer',
            'used_monthly_payroll' => 'integer',
            'employee_count' => 'integer',
        ];
    }
}
