<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DepositRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:100',
            'currency' => 'sometimes|string|in:SYP,USD',
            'reference_type' => 'sometimes|string|max:50',
            'reference_id' => 'sometimes|string|max:50',
            'channel' => 'sometimes|string|in:api,mobile_app,agent,ussd',
            'description' => 'sometimes|string|max:255',
        ];
    }
}
