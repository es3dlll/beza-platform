# 07 - قواعد التحقق (Validation Rules) - تسجيل الوكيل

## FormRequest مع رسائل بالعربية

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ValidLocationBounds;
use App\Rules\UniqueNationalId;
use App\Rules\ValidSaudiPhone;

class RegisterAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:5',
                'max:150',
                'regex:/^[\p{Arabic}\s]+$/u',
            ],
            'phone' => [
                'required',
                'string',
                new ValidSaudiPhone,
                Rule::unique('agent_requests', 'phone')
                    ->whereNull('deleted_at'),
                Rule::unique('agents', 'phone'),
            ],
            'national_id' => [
                'required',
                'string',
                'size:10',
                'regex:/^[12]\d{9}$/',
                new UniqueNationalId,
            ],
            'location' => [
                'required',
                'array',
                'size:2',
            ],
            'location.lat' => [
                'required',
                'numeric',
                'between:16.5,32.5',
            ],
            'location.lng' => [
                'required',
                'numeric',
                'between:34.5,56.0',
            ],
            'service_type' => [
                'required',
                'string',
                Rule::in(['transfer', 'bill_payment', 'cash_in_cash_out', 'all']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'حقل الاسم الكامل مطلوب.',
            'full_name.min' => 'يجب أن يكون الاسم الكامل على الأقل 5 أحرف.',
            'full_name.max' => 'يجب ألا يتجاوز الاسم الكامل 150 حرفاً.',
            'full_name.regex' => 'يجب أن يحتوي الاسم الكامل على أحرف عربية فقط.',

            'phone.required' => 'حقل رقم الهاتف مطلوب.',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل من قبل وكيل آخر.',

            'national_id.required' => 'حقل رقم الهوية الوطنية مطلوب.',
            'national_id.size' => 'يجب أن يكون رقم الهوية الوطنية 10 أرقام.',
            'national_id.regex' => 'يجب أن يبدأ رقم الهوية الوطنية بـ 1 أو 2.',

            'location.required' => 'حقل الموقع مطلوب.',
            'location.array' => 'يجب أن يكون الموقع مصفوفة تحتوي على lat و lng.',
            'location.lat.required' => 'خط العرض مطلوب.',
            'location.lat.between' => 'خط العرض يجب أن يكون بين 16.5 و 32.5.',
            'location.lng.required' => 'خط الطول مطلوب.',
            'location.lng.between' => 'خط الطول يجب أن يكون بين 34.5 و 56.0.',

            'service_type.required' => 'حقل نوع الخدمة مطلوب.',
            'service_type.in' => 'نوع الخدمة يجب أن يكون واحداً من: transfer, bill_payment, cash_in_cash_out, all.',
        ];
    }
}
```

## قواعد مخصصة

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Agent;
use App\Models\AgentRequest;

class UniqueNationalId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Agent::where('national_id', $value)->exists()
            || AgentRequest::where('national_id', $value)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();

        if ($exists) {
            $fail('رقم الهوية الوطنية مستخدم بالفعل.');
        }
    }
}

class ValidSaudiPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cleaned = preg_replace('/[^0-9]/', '', $value);

        if (!preg_match('/^(?:9665|05)[0-9]{8}$/', $cleaned)) {
            $fail('رقم الهاتف يجب أن يكون رقم جوال سعودي صحيح (05XXXXXXXX).');
        }
    }
}

class ValidLocationBounds implements ValidationRule
{
    private const LAT_MIN = 16.5;
    private const LAT_MAX = 32.5;
    private const LNG_MIN = 34.5;
    private const LNG_MAX = 56.0;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $lat = $value['lat'] ?? null;
        $lng = $value['lng'] ?? null;

        if ($lat < self::LAT_MIN || $lat > self::LAT_MAX) {
            $fail('خط العرض خارج النطاق المسموح به للمملكة العربية السعودية.');
        }

        if ($lng < self::LNG_MIN || $lng > self::LNG_MAX) {
            $fail('خط الطول خارج النطاق المسموح به للمملكة العربية السعودية.');
        }
    }
}
```

## التحقق على مستوى الخدمة

```php
<?php

namespace App\Services;

use App\Exceptions\AgentAlreadyExistsException;
use App\Exceptions\LocationOutOfBoundsException;
use App\Exceptions\CommissionRateInvalidException;
use App\Models\Agent;
use App\Models\User;

class AgentValidationService
{
    public function validateNewAgent(User $user, array $data): void
    {
        $this->validateNotAlreadyAgent($user);
        $this->validateLocation($data['location'] ?? []);
        $this->validateCommissionRate($data['commission_rate'] ?? null);
    }

    private function validateNotAlreadyAgent(User $user): void
    {
        $existingAgent = Agent::where('user_id', $user->id)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if ($existingAgent) {
            throw new AgentAlreadyExistsException($user->id);
        }
    }

    private function validateLocation(array $location): void
    {
        $lat = $location['lat'] ?? 0;
        $lng = $location['lng'] ?? 0;

        if ($lat < 16.5 || $lat > 32.5 || $lng < 34.5 || $lng > 56.0) {
            throw new LocationOutOfBoundsException($lat, $lng);
        }
    }

    private function validateCommissionRate(?float $rate): void
    {
        if ($rate !== null && ($rate < 0.001 || $rate > 0.100)) {
            throw new CommissionRateInvalidException($rate);
        }
    }
}
```

## ملخص قواعد التحقق

| الحقل | القاعدة | رسالة الخطأ |
|-------|---------|-------------|
| full_name | مطلوب، نص، 5-150 حرف، عربي فقط | الاسم الكامل مطلوب ويجب أن يكون بالعربية |
| phone | مطلوب، رقم سعودي صحيح، فريد | رقم الهاتف مستخدم أو غير صحيح |
| national_id | مطلوب، 10 أرقام، يبدأ بـ 1 أو 2، فريد | رقم الهوية غير صحيح أو مستخدم |
| location.lat | مطلوب، رقمي، بين 16.5-32.5 | خط العرض خارج نطاق المملكة |
| location.lng | مطلوب، رقمي، بين 34.5-56.0 | خط الطول خارج نطاق المملكة |
| service_type | مطلوب، قيمة محددة | نوع الخدمة غير صحيح |
