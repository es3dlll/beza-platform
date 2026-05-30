<?php

declare(strict_types=1);

namespace Modules\IAM\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:policies,name'],
            'resource' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:50'],
            'effect' => ['required', 'string', 'in:allow,deny'],
            'conditions' => ['nullable', 'array'],
        ];
    }
}
