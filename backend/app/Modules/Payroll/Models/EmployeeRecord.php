<?php

declare(strict_types=1);

namespace Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Model;

final class EmployeeRecord extends Model
{
    protected $table = 'employee_records';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'employer_id', 'full_name', 'full_name_ar', 'phone', 'national_id',
        'job_title', 'department', 'base_salary', 'currency', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
