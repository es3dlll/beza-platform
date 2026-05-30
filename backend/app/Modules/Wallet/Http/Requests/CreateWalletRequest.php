<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWalletRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'currency' => 'sometimes|string|in:SYP,USD',
            'kyc_tier_required' => 'sometimes|integer|min:1|max:3',
            'daily_limit' => 'sometimes|integer|min:0|max:100000000',
        ];
    }
}
