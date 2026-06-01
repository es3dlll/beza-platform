# 07 - قواعد التحقق (Validation Rules) للتسوية

## نظرة عامة
قواعد التحقق تضمن صحة بيانات طلبات التسوية قبل معالجتها. تشمل التحقق من صحة الحساب البنكي، الحد الأدنى للمبلغ، وتكرار الطلبات.

```php
<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettlementRequest extends FormRequest
{
    /**
     * التحقق من صلاحية التاجر لتقديم طلب التسوية
     */
    public function authorize(): bool
    {
        // التأكد من أن التاجر لديه صلاحية السحب
        $merchant = $this->user();
        return $merchant && $merchant->can('request_settlement');
    }

    /**
     * قواعد التحقق من طلب التسوية
     */
    public function rules(): array
    {
        return [
            // العملة المطلوب التسوية بها
            'currency' => [
                'required',
                'string',
                Rule::in(['SYP', 'USD', 'EUR', 'AED']),
            ],

            // المبلغ (اختياري - إذا لم يحدد يؤخذ كل الرصيد)
            'amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            // الحساب البنكي (اختياري - إذا لم يحدد يستخدم الافتراضي)
            'bank_account_id' => [
                'nullable',
                'integer',
                'exists:merchant_bank_accounts,id,merchant_id,' . $this->user()->id,
            ],

            // نوع التسوية
            'settlement_type' => [
                'sometimes',
                'string',
                Rule::in(['instant', 'daily', 'weekly', 'monthly']),
            ],
        ];
    }

    /**
     * رسائل الخطأ المخصصة بالعربية
     */
    public function messages(): array
    {
        return [
            'currency.required'      => 'العملة مطلوبة',
            'currency.in'            => 'العملة غير مدعومة، العملات المدعومة: SYP, USD, EUR, AED',
            'amount.numeric'         => 'المبلغ يجب أن يكون رقماً',
            'amount.min'             => 'المبلغ يجب أن يكون أكبر من صفر',
            'amount.max'             => 'المبلغ كبير جداً',
            'bank_account_id.exists' => 'الحساب البنكي غير موجود أو لا يتبع هذا التاجر',
            'settlement_type.in'     => 'نوع التسوية غير مدعوم',
        ];
    }
}
```

## قواعد التحقق من الحساب البنكي (IBAN Validation)

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IbanRule implements ValidationRule
{
    /**
     * التحقق من صيغة IBAN الدولية
     *
     * IBAN: 2 حروف دولة + 2 رقم تحقق + 1-30 رقم/حرف
     * مثال: SA03 8000 0000 6080 1016 7519 (السعودية)
     *       AE07 0331 2345 6789 0123 456 (الإمارات)
     *       JO71 CBJO 0000 0000 0000 1234 5678 90 (الأردن)
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $iban = strtoupper(str_replace(' ', '', $value));

        // الطول يجب أن يكون بين 15 و 34 حرفاً
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            $fail('رقم IBAN غير صحيح. الطول يجب أن يكون بين 15 و 34 حرفاً.');
            return;
        }

        // التحقق من أول حرفين (رمز الدولة)
        $countryCode = substr($iban, 0, 2);
        $supportedCountries = ['SA', 'AE', 'QA', 'KW', 'BH', 'OM', 'JO', 'EG', 'TR'];

        if (!in_array($countryCode, $supportedCountries)) {
            $fail('رمز الدولة في IBAN غير مدعوم. الدول المدعومة: السعودية، الإمارات، قطر، الكويت، البحرين، عمان، الأردن، مصر، تركيا');
            return;
        }

        // التحقق من صيغة IBAN باستخدام Mod 97
        $ibanCheck = substr($iban, 4) . substr($iban, 0, 4);
        $ibanNumeric = '';

        for ($i = 0; $i < strlen($ibanCheck); $i++) {
            $char = $ibanCheck[$i];
            $ibanNumeric .= ctype_alpha($char) ? (ord($char) - 55) : $char;
        }

        // Mod 97 check
        $remainder = 0;
        for ($i = 0; $i < strlen($ibanNumeric); $i++) {
            $remainder = (($remainder * 10 + (int)$ibanNumeric[$i]) % 97);
        }

        if ($remainder !== 1) {
            $fail('رقم IBAN غير صحيح. فشل التحقق من Mod 97.');
        }
    }
}

// الاستخدام في FormRequest
// 'iban' => ['required', 'string', new IbanRule()],
```

## قواعد إضافية للتحقق من التسوية

```php
<?php

namespace App\Services;

use App\Exceptions\MinimumSettlementNotMetException;
use App\Exceptions\PendingSettlementExistsException;
use App\Models\MerchantBankAccount;
use App\Models\MerchantSettlement;
use Illuminate\Support\Facades\Validator;

class SettlementValidationService
{
    /**
     * التحقق من شروط التسوية قبل التنفيذ
     *
     * المتطلبات:
     * 1. لا يوجد طلب تسوية معلق سابق
     * 2. الرصيد لا يقل عن الحد الأدنى (50 USD)
     * 3. الحساب البنكي نشط وصحيح
     * 4. لم يتم تجاوز الحد الأقصى لتكرار التسوية
     */
    public function validateSettlementRequest(
        Merchant $merchant,
        string $currency,
        ?float $amount = null,
        ?int $bankAccountId = null
    ): void {
        // 1. التحقق من عدم وجود تسوية معلقة
        $pendingExists = MerchantSettlement::where('merchant_id', $merchant->id)
            ->where('currency', $currency)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($pendingExists) {
            throw new PendingSettlementExistsException();
        }

        // 2. التحقق من الحد الأدنى للتسوية
        $minAmount = $this->getMinimumSettlementAmount($currency);
        $walletBalance = $merchant->wallet($currency)->balance;

        if ($walletBalance < $minAmount) {
            throw new MinimumSettlementNotMetException($minAmount, $currency);
        }

        if ($amount !== null && $amount < $minAmount) {
            throw new MinimumSettlementNotMetException($minAmount, $currency);
        }

        // 3. التحقق من الحساب البنكي
        $bankAccount = $bankAccountId
            ? MerchantBankAccount::findOrFail($bankAccountId)
            : $merchant->defaultBankAccount;

        if (!$bankAccount || !$bankAccount->is_active) {
            throw new \App\Exceptions\BankAccountNotActiveException();
        }

        // 4. التحقق من التكرار (مرة كل 24 ساعة للتسوية الفورية)
        if ($this->hasExceededFrequencyLimit($merchant)) {
            throw new \App\Exceptions\SettlementFrequencyExceededException();
        }
    }

    /**
     * الحد الأدنى للتسوية حسب العملة
     */
    private function getMinimumSettlementAmount(string $currency): float
    {
        return match ($currency) {
            'USD' => 50.00,
            'EUR' => 45.00,
            'AED' => 200.00,
            'SYP' => 250000.00,
            default => 50.00,
        };
    }

    /**
     * التحقق من عدم تجاوز حد التكرار
     */
    private function hasExceededFrequencyLimit(Merchant $merchant): bool
    {
        // حد أقصى: تسوية فورية واحدة كل 24 ساعة
        return MerchantSettlement::where('merchant_id', $merchant->id)
            ->where('type', 'instant')
            ->where('created_at', '>=', now()->subDay())
            ->exists();
    }
}
```

## ملخص قواعد التحقق

| الحقل | القاعدة | رسالة الخطأ |
|-------|---------|------------|
| currency | required, in:SYP,USD,EUR,AED | العملة غير مدعومة |
| amount | nullable, numeric, min:0 | المبلغ يجب أن يكون رقماً موجباً |
| bank_account_id | nullable, exists + merchant_id | الحساب البنكي غير موجود |
| iban | string, IbanRule | رقم IBAN غير صحيح |
| settlement_type | in:instant,daily,weekly,monthly | نوع التسوية غير مدعوم |
| minimum amount | ≥ 50 USD (حسب العملة) | الحد الأدنى للتسوية 50 USD |
| duplicate | لا يوجد pending/processing مسبق | لديك طلب تسوية معلق بالفعل |
