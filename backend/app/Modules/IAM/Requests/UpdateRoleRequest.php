<?php

declare(strict_types=1);

namespace Modules\IAM\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($this->route('role'))],
            'description' => ['nullable', 'string', 'max:255'],
            'guard_name' => ['sometimes', 'string', 'max:20'],
        ];
    }
}
