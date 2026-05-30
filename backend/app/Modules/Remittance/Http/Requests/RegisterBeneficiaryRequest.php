<?php

declare(strict_types=1);

namespace Modules\Remittance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'full_name_ar' => 'required|string|max:100',
            'full_name_en' => 'sometimes|nullable|string|max:100',
            'phone' => 'required|string|max:20',
            'national_id' => 'sometimes|nullable|string|max:50',
            'relationship' => 'required|string|in:family,friend,colleague,client,other',
            'governorate' => 'sometimes|nullable|string|max:50',
            'city' => 'sometimes|nullable|string|max:50',
            'address' => 'sometimes|nullable|string|max:500',
            'metadata' => 'sometimes|nullable|array',
        ];
    }
}
