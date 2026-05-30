<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin' => [
                'required',
                'string',
                'size:6',
                'regex:/^\d{6}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => __('identity::messages.pin_invalid_format'),
            'pin.size' => __('identity::messages.pin_invalid_format'),
            'pin.regex' => __('identity::messages.pin_invalid_format'),
        ];
    }
}
