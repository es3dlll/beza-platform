<?php

declare(strict_types=1);

namespace Modules\Cards\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardLimitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'daily_limit' => 'sometimes|integer|min:10000',
            'weekly_limit' => 'sometimes|integer|min:10000',
            'monthly_limit' => 'sometimes|integer|min:10000',
            'single_txn_limit' => 'sometimes|integer|min:1000',
        ];
    }
}
