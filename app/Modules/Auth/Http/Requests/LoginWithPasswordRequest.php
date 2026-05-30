<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginWithPasswordRequest extends FormRequest
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
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'device_id' => 'sometimes|string|max:255',
            'device_name' => 'sometimes|string|max:255',
            'device_type' => 'sometimes|string|in:mobile,tablet,web,pos',
            'fcm_token' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('identity::messages.phone_required'),
            'phone.regex' => __('identity::messages.phone_invalid_format'),
            'password.required' => __('identity::messages.password_required'),
            'password.min' => __('identity::messages.password_min_length'),
        ];
    }
}
