<?php

declare(strict_types=1);

namespace Modules\IAM\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,id'],
        ];
    }
}
