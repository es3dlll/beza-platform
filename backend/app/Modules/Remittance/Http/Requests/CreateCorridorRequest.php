<?php

declare(strict_types=1);

namespace Modules\Remittance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCorridorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('remittance.corridors.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'source_country' => 'required|string|size:3',
            'source_currency' => 'required|string|size:3',
            'target_currency' => 'sometimes|string|size:3',
            'fx_rate_source' => 'sometimes|string|in:cbs_official,market,corridor,internal',
            'fixed_spread_pct' => 'sometimes|numeric|min:0|max:100',
            'fee_type' => 'sometimes|string|in:percentage,fixed,tiered',
            'fee_structure' => 'sometimes|nullable|array',
            'min_amount' => 'sometimes|integer|min:0',
            'max_amount' => 'sometimes|integer|min:0',
            'daily_limit_per_sender' => 'sometimes|integer|min:0',
            'monthly_limit_per_sender' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'supported_payout_methods' => 'sometimes|array',
            'supported_payout_methods.*' => 'string|in:wallet,agent,bank',
            'compliance_requirements' => 'sometimes|nullable|array',
            'partner_name' => 'sometimes|nullable|string|max:100',
        ];
    }
}
