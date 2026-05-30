<?php

declare(strict_types=1);

namespace Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterEmployerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:150',
            'company_name_ar' => 'required|string|max:150',
            'phone' => 'required|string|max:20',
            'governorate' => 'required|string|max:50',
            'city' => 'required|string|max:50',
            'commercial_registration' => 'sometimes|nullable|string|max:50',
            'tax_number' => 'sometimes|nullable|string|max:50',
            'email' => 'sometimes|nullable|email|max:100',
            'address' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
