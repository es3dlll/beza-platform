<?php

declare(strict_types=1);

namespace Modules\Merchant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MerchantPayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'qr_code' => 'required|string|max:64',
            'merchant_id' => 'required|string|size:26',
            'amount' => 'required|integer|min:500',
            'store_id' => 'sometimes|nullable|string|size:26',
        ];
    }
}
