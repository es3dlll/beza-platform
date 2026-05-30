<?php

declare(strict_types=1);

namespace Modules\Savings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSavingsGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'name_ar' => 'sometimes|nullable|string|max:100',
            'target_amount' => 'required|integer|min:10000',
            'target_date' => 'sometimes|nullable|date|after:today',
            'category' => 'sometimes|nullable|string|max:50',
            'icon' => 'sometimes|nullable|string|max:50',
            'color' => 'sometimes|nullable|string|max:7',
            'auto_sweep_enabled' => 'sometimes|boolean',
            'auto_sweep_amount' => 'required_if:auto_sweep_enabled,true|integer|min:1000',
            'auto_sweep_frequency' => 'required_if:auto_sweep_enabled,true|string|in:daily,weekly,monthly',
        ];
    }
}
