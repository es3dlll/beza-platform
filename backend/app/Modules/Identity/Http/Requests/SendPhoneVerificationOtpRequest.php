<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendPhoneVerificationOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
