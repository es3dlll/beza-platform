<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'sometimes|string|max:255',
            'full_name_ar' => 'sometimes|string|max:255',
            'national_id' => 'sometimes|string|size:11',
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|string|in:male,female',
            'address' => 'sometimes|string|max:500',
            'city' => 'sometimes|string|max:100',
            'province' => 'sometimes|string|max:100',
        ];
    }
}
