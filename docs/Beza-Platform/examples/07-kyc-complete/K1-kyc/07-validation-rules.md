# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request — KycSubmitRequest

```php
<?php
// app/Http/Requests/KycSubmitRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KycSubmitRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doc_type' => [
                'required',
                Rule::in(['ID', 'Passport', 'Driver_License']),
            ],
            'front_id' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120', // 5MB
            ],
            'back_id' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
            'selfie' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
            'address_proof' => [
                'required',
                'mimes:jpg,jpeg,png,pdf',
                'max:10240', // 10MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'front_id.required'   => 'صورة الهوية الأمامية مطلوبة',
            'back_id.required'    => 'صورة الهوية الخلفية مطلوبة',
            'selfie.required'     => 'الصورة الشخصية مطلوبة',
            'address_proof.required' => 'إثبات العنوان مطلوب',
            '*.image'             => 'الملف يجب أن يكون صورة',
            '*.max'               => 'حجم الملف يتجاوز الحد المسموح',
        ];
    }
}
```

## التحقق الإضافي

```php
// 1. المستخدم لم يسبق له رفع KYC pending
if (in_array($user->kyc_status, ['pending', 'verified'])) {
    throw new KycAlreadySubmittedException($user->kyc_status);
}

// 2. الفحص التلقائي لدقة الصور (في VerificationService)
// - التحقق من أبعاد الصورة (min 800x600)
// - التحقق من dpi (min 300)
// - التحقق من عدم وجود وجوه متعددة في selfie
```

## سبب كل قاعدة

| القاعدة | السبب |
|---------|-------|
| files max 5-10MB | توازن بين الجودة وحجم التخزين |
| jpg/jpeg/png only | تنسيقات الصور القياسية |
| 4 documents required | متطلبات AML/CTF الكاملة |
| doc_type محدود | أنواع الهوية المعترف بها |
