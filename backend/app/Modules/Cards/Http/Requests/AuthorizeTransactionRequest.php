<?php

declare(strict_types=1);

namespace Modules\Cards\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AuthorizeTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:100',
            'type' => 'sometimes|string|in:purchase,atm_withdrawal',
            'currency' => 'sometimes|string|size:3',
            'merchant_name' => 'sometimes|nullable|string|max:100',
            'merchant_category' => 'sometimes|nullable|string|max:50',
            'merchant_country' => 'sometimes|nullable|string|size:2',
            'channel' => 'sometimes|nullable|string|in:pos,atm,ecommerce,contactless',
        ];
    }
}
