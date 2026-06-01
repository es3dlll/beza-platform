# 07 - قواعد التحقق (Validation Rules)

## SettingsRequest (إعدادات عامة)

```php
<?php
// app/Http/Requests/Admin/SettingsRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'maintenance_mode' => ['nullable', 'boolean'],
            'kyc_required'     => ['nullable', 'boolean'],
        ];
    }
}
```

## FeeSettingsRequest (الرسوم)

```php
<?php
// app/Http/Requests/Admin/FeeSettingsRequest.php

class FeeSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'transfer'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'exchange'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'card_load'     => ['nullable', 'numeric', 'min:0', 'max:100'],
            'merchant_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'merchant_fixed'   => ['nullable', 'numeric', 'min:0'],
            'agent_cash_out'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'withdraw_bank'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deposit_card'     => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.numeric' => 'القيمة يجب أن تكون رقماً',
            '*.min'     => 'القيمة يجب أن تكون 0 أو أكثر',
            '*.max'     => 'الرسوم لا تتجاوز 100%',
        ];
    }
}
```

## LimitSettingsRequest (الحدود)

```php
<?php
// app/Http/Requests/Admin/LimitSettingsRequest.php

class LimitSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'daily_transfer_usd' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'daily_transfer_syp' => ['nullable', 'numeric', 'min:1', 'max:9999999999'],
            'min_deposit_usd'    => ['nullable', 'numeric', 'min:1'],
            'min_deposit_syp'    => ['nullable', 'numeric', 'min:100'],
            'max_wallet_balance' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
```

## ExchangeRateRequest (سعر الصرف)

```php
<?php
// app/Http/Requests/Admin/ExchangeRateRequest.php

class ExchangeRateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'rate'   => ['required', 'numeric', 'min:1'],
            'margin' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'rate.required'   => 'سعر الصرف مطلوب',
            'rate.min'        => 'سعر الصرف يجب أن يكون أكبر من 0',
            'margin.min'      => 'هامش الربح لا يمكن أن يكون سالباً',
            'margin.max'      => 'هامش الربح لا يتجاوز 100%',
        ];
    }
}
```
