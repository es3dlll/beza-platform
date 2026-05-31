# 07 - قواعد التحقق (Validation Rules)

## CommodityBuyRequest

```php
<?php
// app/Http/Requests/CommodityBuyRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommodityBuyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // يتم التحقق عبر auth:api في الـ middleware
    }

    public function rules(): array
    {
        return [
            'commodity' => [
                'required',
                'string',
                'in:gold,silver',
            ],
            'amount_spent' => [
                'required',
                'numeric',
                'min:1',
                'max:9999999.99',
            ],
            'currency' => [
                'required',
                'string',
                'in:SYP,USD',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'commodity.required'   => 'السلعة مطلوبة (gold أو silver)',
            'commodity.in'         => 'السلعة يجب أن تكون gold أو silver',
            'amount_spent.required'=> 'المبلغ المراد إنفاقه مطلوب',
            'amount_spent.numeric' => 'المبلغ يجب أن يكون رقماً',
            'amount_spent.min'     => 'الحد الأدنى للإنفاق هو 1',
            'currency.required'    => 'العملة مطلوبة',
            'currency.in'          => 'العملة يجب أن تكون SYP أو USD',
        ];
    }

    /**
     * تجهيز البيانات قبل التحقق
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'commodity' => strtolower($this->input('commodity')),
            'currency'  => strtoupper($this->input('currency')),
        ]);
    }
}
```

## CommoditySellRequest

```php
<?php
// app/Http/Requests/CommoditySellRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommoditySellRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commodity' => [
                'required',
                'string',
                'in:gold,silver',
            ],
            'grams' => [
                'required',
                'numeric',
                'min:0.1',
                'max:999999.9999',
            ],
            'currency' => [
                'required',
                'string',
                'in:SYP,USD',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'commodity.required' => 'السلعة مطلوبة',
            'commodity.in'       => 'السلعة يجب أن تكون gold أو silver',
            'grams.required'     => 'عدد الجرامات مطلوب',
            'grams.numeric'      => 'عدد الجرامات يجب أن يكون رقماً',
            'grams.min'          => 'الحد الأدنى للبيع هو 0.1 جرام',
            'currency.required'  => 'العملة مطلوبة',
            'currency.in'        => 'العملة يجب أن تكون SYP أو USD',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'commodity' => strtolower($this->input('commodity')),
            'currency'  => strtoupper($this->input('currency')),
        ]);
    }
}
```

## CommodityPriceAlertRequest (اختياري)

```php
<?php
// app/Http/Requests/CommodityPriceAlertRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommodityPriceAlertRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'commodity' => 'required|string|in:gold,silver',
            'target_price' => 'required|numeric|min:0.01',
            'direction' => 'required|string|in:above,below',
        ];
    }

    public function messages(): array
    {
        return [
            'commodity.required'    => 'السلعة مطلوبة',
            'target_price.required' => 'السعر المستهدف مطلوب',
            'direction.in'          => 'الاتجاه يجب أن يكون above أو below',
        ];
    }
}
```

## جدول ملخص التحقق

| الحقل | قواعد التحقق | سبب الرفض |
|-------|-------------|-----------|
| commodity | required, in:gold,silver | السلعة غير معروفة |
| amount_spent (buy) | required, numeric, min:1 | مبلغ غير صحيح أو أقل من 1 |
| currency | required, in:SYP,USD | عملة غير مدعومة |
| grams (sell) | required, numeric, min:0.1 | أقل من 0.1 جرام |
| price_type | required, in:market,limit | نوع أمر غير معروف |
| limit_price | required_if:price_type,limit | سعر الحد مطلوب لأوامر limit |
| target_price (alert) | numeric, min:0.01 | سعر غير صحيح |
| direction (alert) | in:above,below | اتجاه غير معروف |

## Business Rules (بعد التحقق)

| القاعدة | مكان التنفيذ | الخطأ |
|---------|-------------|-------|
| السوق مفتوح (ليس weekend) | CommodityService | MarketClosedException |
| السعر خلال 30 ثانية | CommodityService | PriceExpiredException |
| رصيد كافٍ | CommodityService + WalletService | InsufficientBalanceException |
| حيازة كافية للبيع | CommodityService | InsufficientHoldingException |
| مرور 24 ساعة على الأقل | CommodityService | MinimumHoldingPeriodException |
| هامش السبريد مقبول (< 5%) | CommodityService + PriceFeedProvider | SpreadTooHighException |
