<?php

declare(strict_types=1);

namespace Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ApproveBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [];
    }
}
