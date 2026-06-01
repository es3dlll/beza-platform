# 07 - قواعد التحقق (Validation Rules)

```php
<?php
namespace AppHttpRequestsMerchant;
use IlluminateFoundationHttpFormRequest;
use IlluminateValidationRule;

class PaymentLinkRequest extends FormRequest
{
    public function rules(): array {
        return [
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'currency'     => ['required', Rule::in(['SYP', 'USD'])],
            'description'  => ['nullable', 'string', 'max:500'],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'expiry_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ];
    }
    public function messages(): array {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'amount.min' => 'أقل مبلغ 0.01',
            'amount.max' => 'أقصى مبلغ 999,999.99',
            'currency.in' => 'العملة غير مدعومة (SYP/USD فقط)',
            'expiry_hours.required' => 'مدة الصلاحية مطلوبة',
            'expiry_hours.min' => 'أقل مدة صلاحية ساعة واحدة',
            'expiry_hours.max' => 'أقصى مدة صلاحية 30 يوماً (720 ساعة)',
            'redirect_url.url' => 'رابط إعادة التوجيه غير صحيح',
        ];
    }
}
```

## شرح قواعد التحقق
- amount: رقم موجب بين 0.01 و 999,999.99
- currency: SYP أو USD فقط
- expiry_hours: بين 1 و 720 ساعة (30 يوم)
- redirect_url: رابط URL صحيح (اختياري)
- رسائل الخطأ بالعربية لتجربة مستخدم أفضل
