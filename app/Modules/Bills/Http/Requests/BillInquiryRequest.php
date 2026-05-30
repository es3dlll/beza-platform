<?php

declare(strict_types=1);

namespace Modules\Bills\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bill_provider_id' => 'required|string|size:26',
            'account_number' => 'required|string|max:50',
        ];
    }
}
