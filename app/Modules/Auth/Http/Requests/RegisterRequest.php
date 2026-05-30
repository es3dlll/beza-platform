<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^963\d{9}$/',
            ],
            'phone_country_code' => 'sometimes|string|size:3',
            'locale' => 'sometimes|string|in:ar,en',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('identity::messages.phone_required'),
            'phone.regex' => __('identity::messages.phone_invalid_format'),
        ];
    }
}
