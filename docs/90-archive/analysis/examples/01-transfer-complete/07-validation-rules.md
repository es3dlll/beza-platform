# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## Form Request — TransferRequest

```php
<?php
// app/Http/Requests/TransferRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    // لمنع التكرار في حال فشل التحقق — إرجاع أول خطأ فقط
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true; // تم التحقق من التوكن قبلها
    }

    public function rules(): array
    {
        return [
            'to_phone' => [
                'required',
                'string',
                'regex:/^[0-9+\-\(\)\s]{7,20}$/',
                Rule::exists('users', 'phone')->where('status', 'active'),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:9999999.99',
            ],
            'currency' => [
                'required',
                'string',
                Rule::in(['SYP', 'USD']),
            ],
            'pin' => [
                'required',
                'string',
                'digits:4',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'to_phone.required'    => 'رقم الهاتف مطلوب',
            'to_phone.exists'      => 'المستخدم غير موجود أو غير نشط',
            'to_phone.regex'       => 'صيغة رقم الهاتف غير صحيحة',
            'amount.required'      => 'المبلغ مطلوب',
            'amount.numeric'       => 'المبلغ يجب أن يكون رقماً',
            'amount.min'           => 'أقل مبلغ للتحويل هو 1',
            'amount.max'           => 'المبلغ يتجاوز الحد المسموح',
            'currency.required'    => 'العملة مطلوبة',
            'currency.in'          => 'العملة غير مدعومة (SYP أو USD فقط)',
            'pin.required'         => 'رمز PIN مطلوب',
            'pin.digits'           => 'PIN يجب أن يكون 4 أرقام',
            'description.max'      => 'الوصف طويل جداً (حد أقصى 255 حرف)',
        ];
    }
}
```

## سبب كل قاعدة

| الحقل | القاعدة | السبب |
|-------|---------|-------|
| `to_phone` | required | لا يمكن التحويل بدون تحديد المستلم |
| `to_phone` | exists:users,phone | المستلم يجب أن يكون مسجلاً في النظام |
| `to_phone` | where:status=active | المستلم يجب أن يكون غير محظور |
| `to_phone` | regex | منع Injection عبر أرقام مشوهة |
| `amount` | numeric | منع القيم النصية |
| `amount` | min:1 | لا يمكن تحويل 0 أو أرقام سالبة |
| `amount` | max:9999999.99 | حماية من تجاوز سعة DECIMAL(15,2) |
| `currency` | in:SYP,USD | فقط العملات المدعومة في Beza |
| `pin` | digits:4 | PIN يكون 4 أرقام فقط |
| `pin` | required | لا يمكن تأكيد المعاملة بدون PIN |

## التحقق الإضافي (بعد Form Request)

هذه التحققات تتم في Service Layer لأنها تحتاج DB Queries:

```php
// في TransferService

// 1. منع التحويل للنفس
if ($fromUser->id === $toUser->id) {
    throw new SelfTransferException();
}

// 2. التحقق من PIN
if (!Hash::check($request->pin, $fromUser->pin_code)) {
    throw new InvalidPinException();
}

// 3. التحقق من الرصيد
$fromWallet = $fromUser->wallets()
    ->where('currency', $request->currency)
    ->where('is_active', true)
    ->firstOrFail();

if ($fromWallet->available_balance < $request->amount) {
    throw new InsufficientBalanceException();
}

// 4. التحقق من الحد اليومي
$dailyTotal = Transaction::where('from_wallet_id', $fromWallet->id)
    ->where('type', 'transfer')
    ->where('status', 'completed')
    ->whereDate('created_at', today())
    ->sum('amount');

$dailyLimit = $request->currency === 'USD' ? 2000 : 2000000;

if (($dailyTotal + $request->amount) > $dailyLimit) {
    throw new DailyLimitExceededException($dailyLimit, $dailyTotal);
}

// 5. التحقق من أن المحفظة الوجهة نشطة
$toWallet = $toUser->wallets()
    ->where('currency', $request->currency)
    ->where('is_active', true)
    ->firstOrFail();
```

## ملخص كل أنواع التحقق

| النوع | أين يتم | الترتيب |
|-------|---------|---------|
| Structural validation | Form Request (rules) | 1 |
| Business validation | TransferService | 2 |
| Database constraints | MySQL (UNIQUE, FK, CHECK) | 3 |
| Atomic safety | DB::transaction + FOR UPDATE | 4 |
