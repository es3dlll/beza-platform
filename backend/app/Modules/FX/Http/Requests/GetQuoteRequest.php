<?php

declare(strict_types=1);

namespace Modules\FX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetQuoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'base_currency' => 'required|string|in:USD,SYP',
            'quote_currency' => 'required|string|in:SYP,USD',
            'amount' => 'required|integer|min:1',
            'rate_type' => 'sometimes|string|in:cbs_official,market',
            'ttl_seconds' => 'sometimes|integer|min:10|max:300',
        ];
    }
}
