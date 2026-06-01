# 07 - قواعد التحقق (Validation Rules)

## FormRequest: CardReportRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to', 'before_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from', 'before_or_equal:today'],
            'category' => ['nullable', 'string', 'max:100', Rule::in([
                'food', 'transport', 'shopping', 'utilities',
                'entertainment', 'health', 'education', 'other',
            ])],
            'status' => ['nullable', Rule::in(['pending', 'completed', 'failed', 'refunded'])],
            'merchant' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['created_at', 'amount', 'merchant'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.before_or_equal' => 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية',
            'date_to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'date_from.date' => 'صيغة التاريخ غير صحيحة',
            'category.in' => 'التصنيف المحدد غير صالح',
            'page.min' => 'رقم الصفحة يجب أن يكون 1 على الأقل',
            'per_page.max' => 'عدد العناصر في الصفحة يجب ألا يتجاوز 100',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('date_from') && empty($this->date_from)) {
            $this->merge(['date_from' => now()->subMonth()->toDateString()]);
        }
        if ($this->has('date_to') && empty($this->date_to)) {
            $this->merge(['date_to' => now()->toDateString()]);
        }
    }
}
```

## Filter Validation Rules

| Field | Rule | Description |
|-------|------|-------------|
| date_from | nullable, date, before:date_to | Start of report range |
| date_to | nullable, date, after:date_from | End of report range |
| category | nullable, in:[...] | Spending category filter |
| status | nullable, in:[...] | Transaction status filter |
| merchant | nullable, string | Partial merchant name search |
| page | integer, min:1 | Pagination page number |
| per_page | integer, min:1, max:100 | Items per page |
| sort_by | in:[created_at,amount,merchant] | Sort column |
| sort_order | in:[asc,desc] | Sort direction |

## Default Values

- `date_from`: 30 days ago if not provided
- `date_to`: today if not provided
- `per_page`: 15
- `page`: 1
- `sort_by`: `created_at`
- `sort_order`: `desc`
