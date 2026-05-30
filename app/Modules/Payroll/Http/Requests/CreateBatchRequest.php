<?php

declare(strict_types=1);

namespace Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period_month' => 'required|string|size:7',
            'notes' => 'sometimes|nullable|string|max:500',
            'employees' => 'required|array|min:1',
            'employees.*.employee_name' => 'required|string|max:100',
            'employees.*.phone' => 'required|string|max:20',
            'employees.*.amount' => 'required|integer|min:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'employees.*.amount.min' => 'Each employee amount must be at least 1,000 SYP',
        ];
    }
}
