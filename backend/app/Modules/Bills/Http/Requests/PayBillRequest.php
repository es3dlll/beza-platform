<?php

declare(strict_types=1);

namespace Modules\Bills\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PayBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bill_payment_id' => 'required|string|size:26',
            'amount' => 'sometimes|integer|min:1',
        ];
    }
}
