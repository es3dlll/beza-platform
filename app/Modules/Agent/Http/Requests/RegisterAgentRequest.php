<?php

declare(strict_types=1);

namespace Modules\Agent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterAgentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'business_name' => 'required|string|max:200',
            'governorate' => 'required|string|max:50',
            'city' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'agent_type' => 'sometimes|string|in:retail,exchange,post_office',
            'area' => 'sometimes|string|max:50',
            'address' => 'sometimes|string|max:255',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'alt_phone' => 'sometimes|string|max:20',
        ];
    }
}
