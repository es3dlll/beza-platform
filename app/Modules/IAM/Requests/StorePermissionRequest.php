<?php

declare(strict_types=1);

namespace Modules\IAM\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:permissions,name'],
            'module' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'guard_name' => ['sometimes', 'string', 'max:20'],
        ];
    }
}
