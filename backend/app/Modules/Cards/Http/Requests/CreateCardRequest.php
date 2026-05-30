<?php

declare(strict_types=1);

namespace Modules\Cards\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'card_type' => 'sometimes|string|in:virtual,prepaid,debit',
            'cardholder_name' => 'required|string|max:100',
            'currency' => 'sometimes|string|size:3',
            'is_virtual' => 'sometimes|boolean',
        ];
    }
}
