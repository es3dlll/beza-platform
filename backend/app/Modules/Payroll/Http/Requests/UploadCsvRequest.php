<?php

declare(strict_types=1);

namespace Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'csv_content' => 'required|string',
            'period_month' => 'required|string|size:7',
            'notes' => 'sometimes|nullable|string|max:500',
        ];
    }
}
