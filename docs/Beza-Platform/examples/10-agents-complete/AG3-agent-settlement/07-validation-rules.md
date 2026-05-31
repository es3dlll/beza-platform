# 07 - قواعد التحقق (Validation Rules)

## Form Request: AgentSettlementRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgentSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:' . $this->getMinimumAmount(),
                'max:99999999.99',
            ],

            'currency' => [
                'required',
                'string',
                'max:3',
                Rule::in(['SYP', 'USD']),
            ],

            'bank_account' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $currency = $this->input('currency', 'SYP');
                    if ($currency === 'SYP') {
                        if (!preg_match('/^[0-9]{15,22}$/', $value)) {
                            $fail('حساب SYP يجب أن يكون رقم حساب مصرفي سوري (15-22 رقم).');
                        }
                    } elseif ($currency === 'USD') {
                        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $value)) {
                            $fail('حساب USD يجب أن يكون رقم IBAN صالح (يبدأ برمز الدولة).');
                        }
                    }
                },
            ],

            'bank_name' => [
                'required',
                'string',
                'max:255',
            ],

            'recipient_name' => [
                'required',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'حقل المبلغ مطلوب.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'الحد الأدنى للمبلغ: 50,000 ل.س أو 50 دولار.',
            'amount.max' => 'المبلغ يتجاوز الحد المسموح.',
            'currency.required' => 'حقل العملة مطلوب.',
            'currency.in' => 'العملة المدعومة: SYP أو USD فقط.',
            'bank_account.required' => 'رقم الحساب المصرفي مطلوب.',
            'bank_name.required' => 'اسم المصرف مطلوب.',
            'recipient_name.required' => 'اسم المستفيد مطلوب.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }

    private function getMinimumAmount(): float
    {
        return $this->input('currency') === 'USD' ? 50 : 50000;
    }
}
```

## قواعد التحقق التجارية (Business Validation)

```php
<?php

namespace App\Services\Validators;

use App\Models\AgentSettlement;
use App\Exceptions\SettlementLimitExceededException;
use App\Exceptions\PendingSettlementExistsException;

class SettlementValidator
{
    private const int DAILY_LIMIT_SYP = 5000000;
    private const int DAILY_LIMIT_USD = 5000;
    private const int MAX_PENDING_REQUESTS = 3;

    public function validateAmount(float $amount, string $currency): void
    {
        $minAmount = $currency === 'USD' ? 50 : 50000;

        if ($amount < $minAmount) {
            throw new \InvalidArgumentException(
                "الحد الأدنى للتسوية: {$minAmount} {$currency}"
            );
        }

        $dailyLimit = $currency === 'USD'
            ? self::DAILY_LIMIT_USD
            : self::DAILY_LIMIT_SYP;

        $dailyTotal = AgentSettlement::where('agent_id', auth()->id())
            ->whereDate('created_at', today())
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->sum('amount');

        if (($dailyTotal + $amount) > $dailyLimit) {
            throw new SettlementLimitExceededException(
                "تجاوز الحد اليومي للتسوية. الحد المسموح: {$dailyLimit} {$currency}"
            );
        }
    }

    public function validatePendingRequests(int $agentId): void
    {
        $pendingCount = AgentSettlement::where('agent_id', $agentId)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        if ($pendingCount >= self::MAX_PENDING_REQUESTS) {
            throw new PendingSettlementExistsException(
                'لديك طلب تسوية قيد المعالجة بالفعل. يرجى الانتظار حتى اكتماله.'
            );
        }
    }

    public function validateAgentBalance(int $agentId, float $amount): void
    {
        $wallet = \App\Models\Wallet::where('user_id', $agentId)->first();

        if (!$wallet || $wallet->balance < $amount) {
            throw new \RuntimeException(
                'رصيد المحفظة غير كافٍ للتسوية.'
            );
        }
    }
}
```

## طبقات التحقق

| الطبقة | الموقع | المهام |
|--------|--------|--------|
| هيكلية (Structural) | Form Request | الأنواع، الحقول المطلوبة، الصيغة |
| تجارية (Business) | SettlementValidator | القواعد المنطقية، الحدود اليومية |
| قاعدة بيانات | MySQL | المفاتيح الخارجية، القيود |
| ذرية (Atomic) | DB::transaction | سباق التزامن |

## أمثلة على طلبات صالحة

### SYP
```json
{
    "amount": 100000,
    "currency": "SYP",
    "bank_account": "1234567890123456789",
    "bank_name": "المصرف التجاري السوري",
    "recipient_name": "أحمد محمد"
}
```

### USD
```json
{
    "amount": 500,
    "currency": "USD",
    "bank_account": "SY0301234567890123456789",
    "bank_name": "Bank of Syria",
    "recipient_name": "Ahmad Mohamad"
}
```
