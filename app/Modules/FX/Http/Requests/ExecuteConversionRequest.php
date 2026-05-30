<?php

declare(strict_types=1);

namespace Modules\FX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExecuteConversionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quote_id' => 'required|string|size:26',
            'from_wallet_id' => 'sometimes|string|size:26',
            'to_wallet_id' => 'sometimes|string|size:26',
        ];
    }
}
