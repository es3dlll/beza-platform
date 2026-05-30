<?php

declare(strict_types=1);

namespace Modules\Agent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CashInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_wallet_id' => 'required|string|size:26',
            'amount' => 'required|integer|min:100|max:50000000',
            'currency' => 'sometimes|string|in:SYP,USD',
            'reference_id' => 'sometimes|string|max:50',
        ];
    }
}
