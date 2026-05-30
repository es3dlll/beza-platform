<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CheckPhoneRequest extends FormRequest
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
