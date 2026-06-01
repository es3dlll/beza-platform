# 07 - قواعد التحقق (Validation Rules)

## RejectRequest (سبب الرفض)

```php
<?php
// app/Http/Requests/Admin/RejectRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'سبب الرفض مطلوب',
            'reason.min'      => 'سبب الرفض يجب أن يكون 10 أحرف على الأقل',
            'reason.max'      => 'سبب الرفض طويل جداً',
        ];
    }
}
```

## التحقق الإضافي في Service

```php
// 1. التحقق من أن الطلب pending
if ($merchant->status !== 'pending') {
    throw new ApplicationAlreadyProcessedException();
}

// 2. التحقق من أن المستندات كاملة
$pendingDocs = $merchant->documents()->where('status', 'pending')->count();
if ($pendingDocs > 0) {
    throw new DocumentsNotReviewedException();
}

// 3. التحقق من KYC
if ($merchant->user->kyc_status !== 'verified') {
    throw new KycNotVerifiedException();
}

// 4. التحقق من عدم وجود تاجر مكرر
if (Merchant::where('commercial_reg_no', $data['commercial_reg_no'])
    ->where('status', 'active')
    ->exists()) {
    throw new DuplicateMerchantException();
}
```

## جدول التحقق

| المستوى | التحقق |
|---------|--------|
| Form Request | reason required + min:10 |
| Service | status = pending |
| Service | KYC verified |
| Service | وثائق كاملة |
| Service | لا يوجد تكرار |
| DB | UNIQUE(user_id) |
