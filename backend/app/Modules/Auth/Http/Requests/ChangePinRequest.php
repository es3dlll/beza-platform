<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChangePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_pin' => [
                'required',
                'string',
                'size:6',
            ],
            'new_pin' => [
                'required',
                'string',
                'size:6',
                'regex:/^\d{6}$/',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_pin.required' => __('identity::messages.pin_invalid_format'),
            'new_pin.required' => __('identity::messages.pin_invalid_format'),
            'new_pin.regex' => __('identity::messages.pin_invalid_format'),
            'new_pin.confirmed' => __('identity::messages.pin_invalid_format'),
        ];
    }
}
