<?php

declare(strict_types=1);

namespace Modules\Fraud\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFraudRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'rule_type' => 'required|string|max:50',
            'description' => 'sometimes|nullable|string|max:1000',
            'parameters' => 'required|array',
            'risk_score' => 'required|integer|min:0|max:1000',
            'severity' => 'sometimes|string|in:low,medium,high,critical',
        ];
    }
}
