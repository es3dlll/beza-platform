<?php

declare(strict_types=1);

namespace Modules\Savings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ContributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1000',
        ];
    }
}
