# 07 - قواعد التحقق من صحة النماذج

## Form Request — ContactRequest

```php
<?php
// app/Http/Requests/ContactRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): true
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:100'],
            'phone'   => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\(\)\s]+$/'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'الاسم مطلوب',
            'email.required'   => 'البريد الإلكتروني مطلوب',
            'email.email'      => 'البريد الإلكتروني غير صحيح',
            'subject.required' => 'الموضوع مطلوب',
            'message.required' => 'الرسالة مطلوبة',
            'message.min'      => 'الرسالة يجب أن تكون 10 أحرف على الأقل',
        ];
    }
}
```

## Form Request — SubscribeRequest

```php
<?php
// app/Http/Requests/SubscribeRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email'  => ['required', 'email', 'max:100', 'unique:subscribers,email'],
            'name'   => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email'    => 'البريد الإلكتروني غير صحيح',
            'email.unique'   => 'هذا البريد مسجل بالفعل',
        ];
    }
}
```

## Form Request — MerchantInquiryRequest

```php
<?php
// app/Http/Requests/MerchantInquiryRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantInquiryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name'   => ['required', 'string', 'max:150'],
            'contact_name'   => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email', 'max:100'],
            'phone'          => ['required', 'string', 'max:20'],
            'business_type'  => ['nullable', 'string', 'max:50'],
            'monthly_volume' => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ];
    }
}
```

## Form Request — AgentInquiryRequest

```php
<?php
// app/Http/Requests/AgentInquiryRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentInquiryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'contact_name' => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:100'],
            'phone'        => ['required', 'string', 'max:20'],
            'city'         => ['required', 'string', 'max:50'],
            'has_office'   => ['boolean'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ];
    }
}
```

## جدول ملخص قواعد التحقق

| الحقل | القاعدة | الشرح |
|-------|---------|-------|
| name | required, max:100 | اسم المرسل |
| email | required, email, max:100 | بريد إلكتروني صحيح |
| email (subscribe) | + unique:subscribers | منع الاشتراك المكرر |
| phone | nullable, regex:/^[0-9+\-()\s]+$/ | أرقام فقط |
| subject | required, max:200 | موضوع الرسالة |
| message | required, min:10, max:5000 | محتوى الرسالة |
| company_name | required, max:150 | اسم الشركة |
| monthly_volume | nullable, numeric, min:0 | حجم المعاملات |
| city | required, max:50 | المدينة |
