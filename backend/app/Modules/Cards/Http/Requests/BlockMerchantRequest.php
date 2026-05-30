<?php

declare(strict_types=1);

namespace Modules\Cards\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BlockMerchantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'merchant_category' => 'required|string|max:50',
            'reason' => 'sometimes|nullable|string|max:500',
        ];
    }
}
