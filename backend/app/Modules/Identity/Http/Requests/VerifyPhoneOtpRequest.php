<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'size:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => __('identity::messages.otp_required'),
            'code.size' => __('identity::messages.otp_invalid_format'),
        ];
    }
}
