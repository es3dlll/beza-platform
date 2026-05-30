<?php

declare(strict_types=1);

namespace Modules\Fraud\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class FraudCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event_type' => 'required|string|max:50',
            'ip_address' => 'sometimes|nullable|string|max:45',
            'device_id' => 'sometimes|nullable|string|max:255',
            'user_agent' => 'sometimes|nullable|string|max:500',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'amount' => 'sometimes|nullable|integer|min:0',
            'iban' => 'sometimes|nullable|string|max:34',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|nullable|email|max:100',
            'full_name' => 'sometimes|nullable|string|max:150',
        ];
    }
}
