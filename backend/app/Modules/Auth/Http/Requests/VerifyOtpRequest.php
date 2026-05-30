<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'code' => 'required|string|size:6',
            'purpose' => 'required|string|in:register,login,change_phone,forgot_pin',
        ];
    }
}
