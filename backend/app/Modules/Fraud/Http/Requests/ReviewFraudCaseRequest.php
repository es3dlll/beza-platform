<?php

declare(strict_types=1);

namespace Modules\Fraud\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewFraudCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'decision' => 'required|string|in:dismissed,resolved',
            'review_notes' => 'sometimes|nullable|string|max:2000',
        ];
    }
}
