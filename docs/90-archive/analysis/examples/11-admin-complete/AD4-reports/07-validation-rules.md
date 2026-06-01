# 07 - قواعد التحقق (Validation Rules)

## ReportFilterRequest

```php
<?php
// app/Http/Requests/Admin/ReportFilterRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date'  => ['nullable', 'date', 'before_or_equal:today'],
            'from'  => ['nullable', 'date', 'required_with:to'],
            'to'    => ['nullable', 'date', 'after_or_equal:from'],
            'year'  => ['nullable', 'integer', 'min:2024', 'max:2099'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'format'=> ['nullable', 'in:json,csv,xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'لا يمكن اختيار تاريخ في المستقبل',
            'from.required_with'   => 'تاريخ البداية مطلوب مع تاريخ النهاية',
            'to.after_or_equal'    => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
        ];
    }
}
```

## التحقق من البيانات

```php
// 1. الفترة لا تتجاوز سنة (للأداء)
if ($from && $to && $from->diffInDays($to) > 365) {
    throw new ReportPeriodTooLongException();
}

// 2. البيانات موجودة
if ($transactions->isEmpty() && $users->isEmpty()) {
    return $this->emptyReport($date); // أصفار بدلاً من خطأ
}
```
