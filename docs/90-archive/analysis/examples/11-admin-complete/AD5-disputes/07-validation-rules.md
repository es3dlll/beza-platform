# 07 - قواعد التحقق (Validation Rules)

## DisputeRequest (تقديم نزاع)

```php
<?php
// app/Http/Requests/DisputeRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DisputeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'reason'         => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string', 'min:20', 'max:5000'],
            'evidence_files' => ['nullable', 'array', 'max:5'],
            'evidence_files.*' => ['file', 'mimes:jpg,png,pdf,doc', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_id.required' => 'رقم المعاملة مطلوب',
            'transaction_id.exists'   => 'المعاملة غير موجودة',
            'reason.required'         => 'سبب النزاع مطلوب',
            'description.required'    => 'وصف النزاع مطلوب',
            'description.min'         => 'وصف النزاع يجب أن يكون 20 حرفاً على الأقل',
            'evidence_files.max'      => 'الحد الأقصى 5 ملفات',
            'evidence_files.*.max'    => 'الملف كبير جداً (الحد الأقصى 10MB)',
        ];
    }
}
```

## ResolveDisputeRequest (حل النزاع)

```php
<?php
// app/Http/Requests/Admin/ResolveDisputeRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveDisputeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'resolution' => ['required', Rule::in(['refund', 'reject', 'partial_refund'])],
            'partial_amount' => ['required_if:resolution,partial_refund', 'numeric', 'min:0.01'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'resolution.required'       => 'القرار مطلوب',
            'resolution.in'             => 'قرار غير صحيح',
            'partial_amount.required_if'=> 'المبلغ الجزئي مطلوب للاسترجاع الجزئي',
            'partial_amount.min'        => 'المبلغ الجزئي يجب أن يكون أكبر من 0',
        ];
    }
}
```

## التحقق الإضافي في Service

```php
// 1. النزاع مفتوح (ليس مقفلاً)
if ($dispute->status !== 'open' && $dispute->status !== 'investigating') {
    throw new DisputeAlreadyResolvedException();
}

// 2. مدة النزاع لم تنته
if ($dispute->isExpired()) {
    // نغلقه تلقائياً
    throw new DisputeExpiredException();
}
```
