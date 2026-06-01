# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request — ExchangeRequest

```php
<?php
// app/Http/Requests/ExchangeRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExchangeRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_currency' => [
                'required',
                'string',
                Rule::in(['SYP', 'USD']),
            ],
            'to_currency' => [
                'required',
                'string',
                Rule::in(['SYP', 'USD']),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:9999999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'from_currency.required' => 'عملة المصدر مطلوبة',
            'from_currency.in'       => 'عملة المصدر غير مدعومة',
            'to_currency.required'   => 'عملة الوجهة مطلوبة',
            'to_currency.in'         => 'عملة الوجهة غير مدعومة',
            'amount.required'        => 'المبلغ مطلوب',
            'amount.numeric'         => 'المبلغ يجب أن يكون رقماً',
            'amount.min'             => 'المبلغ صغير جداً',
            'amount.max'             => 'المبلغ كبير جداً',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| `from_currency` | required | يجب تحديد مصدر العملة |
| `from_currency` | in:SYP,USD | فقط العملات المدعومة |
| `to_currency` | required | يجب تحديد عملة الوجهة |
| `to_currency` | in:SYP,USD | فقط العملات المدعومة |
| `amount` | numeric | منع القيم النصية |
| `amount` | min:0.01 | منع الصفر والقيم السالبة |
| `amount` | max:9999999.99 | حماية DECIMAL(15,2) |

## التحقق الإضافي (بعد Form Request)

هذه التحققات تتم في ExchangeService:

```php
// في ExchangeService

// 1. منع الصرافة لنفس العملة
if ($request->from_currency === $request->to_currency) {
    throw new SameCurrencyExchangeException();
}

// 2. التحقق من الحد الأدنى
$minAmount = config("beza.exchange.min_amounts.{$request->from_currency}");
if ($request->amount < $minAmount) {
    throw new MinimumAmountException($minAmount, $request->from_currency);
}

// 3. التحقق من وجود المحفظة المصدر
$fromWallet = $user->wallets()
    ->where('currency', $request->from_currency)
    ->where('is_active', true)
    ->firstOrFail();

// 4. التحقق من الرصيد (amount + fee)
$rate = $this->rateService->getRate(
    $request->from_currency,
    $request->to_currency
);
$fee = $this->rateService->calculateFee($request->amount, $rate['fee_percentage']);
$totalDeduction = $request->amount + $fee;

if ($fromWallet->available_balance < $totalDeduction) {
    throw new InsufficientBalanceException(
        available: (float) $fromWallet->available_balance,
        required:  $totalDeduction,
    );
}
```

## ملخص كل أنواع التحقق

| النوع | أين يتم | الترتيب |
|-------|---------|---------|
| Structural validation | ExchangeRequest (rules) | 1 |
| Business validation | ExchangeService | 2 |
| Database constraints | MySQL (UNIQUE, FK, CHECK) | 3 |
| Atomic safety | DB::transaction + FOR UPDATE | 4 |
