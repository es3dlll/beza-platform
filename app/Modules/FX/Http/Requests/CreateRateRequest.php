<?php

declare(strict_types=1);

namespace Modules\FX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateRateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'base_currency' => 'required|string|in:USD,SYP,EUR,TRY',
            'quote_currency' => 'required|string|in:SYP,USD',
            'mid_rate' => 'required|numeric|min:0.000001',
            'rate_type' => 'required|string|in:cbs_official,market,corridor,internal',
            'source' => 'required|string|max:50',
            'spread_pct' => 'sometimes|numeric|min:0|max:10',
            'bid_rate' => 'sometimes|numeric|min:0.000001',
            'ask_rate' => 'sometimes|numeric|min:0.000001',
            'valid_to' => 'sometimes|date',
        ];
    }
}
