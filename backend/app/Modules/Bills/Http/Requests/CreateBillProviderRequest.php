<?php

declare(strict_types=1);

namespace Modules\Bills\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBillProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('bills.providers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:30|unique:bill_providers,code',
            'name' => 'required|string|max:100',
            'name_ar' => 'required|string|max:100',
            'category' => 'required|string|in:telecom,utility,government,installment',
            'account_label' => 'required|string|max:100',
            'account_format_regex' => 'sometimes|nullable|string|max:100',
            'supported_account_types' => 'sometimes|nullable|array',
            'supported_account_types.*' => 'string',
            'fee_percentage' => 'sometimes|numeric|min:0|max:100',
            'fee_min_syp' => 'sometimes|integer|min:0',
            'fee_max_syp' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'integration_config' => 'sometimes|nullable|array',
        ];
    }
}
