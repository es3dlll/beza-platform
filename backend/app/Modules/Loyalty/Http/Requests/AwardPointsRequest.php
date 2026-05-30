<?php

declare(strict_types=1);

namespace Modules\Loyalty\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AwardPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|string|size:26',
            'points' => 'required|integer|min:1',
            'reference_type' => 'sometimes|nullable|string|max:50',
            'reference_id' => 'sometimes|nullable|string|max:50',
            'description' => 'sometimes|nullable|string|max:500',
        ];
    }
}
