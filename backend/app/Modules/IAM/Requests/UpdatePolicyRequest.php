<?php

declare(strict_types=1);

namespace Modules\IAM\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('policies', 'name')->ignore($this->route('policy'))],
            'resource' => ['sometimes', 'string', 'max:100'],
            'action' => ['sometimes', 'string', 'max:50'],
            'effect' => ['sometimes', 'string', 'in:allow,deny'],
            'conditions' => ['nullable', 'array'],
        ];
    }
}
