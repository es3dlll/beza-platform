# 07 - قواعد الكشف (Detection Rules)

## قواعد الكشف في النظام

```php
<?php

namespace App\Services\FraudDetection\Rules;

interface RuleInterface
{
    public function evaluate(FraudContext $context): RuleResult;
}
```

### القاعدة 1: سرعة المعاملات

```php
class TransactionVelocityRule implements RuleInterface
{
    private const MAX_TRANSACTIONS_PER_MINUTE = 5;
    private const WEIGHT = 30; // وزن القاعدة من 100

    public function evaluate(FraudContext $context): RuleResult
    {
        $count = Transaction::where('from_wallet_id', $context->fromWallet->id)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($count >= self::MAX_TRANSACTIONS_PER_MINUTE) {
            return new RuleResult(
                triggered: true,
                score: self::WEIGHT,
                rule: 'high_transaction_velocity',
                message: 'سرعة معاملات غير طبيعية: ' . $count . ' معاملة في الدقيقة'
            );
        }

        return new RuleResult(false, 0, '', '');
    }
}
```

### القاعدة 2: مبلغ كبير

```php
class HighAmountRule implements RuleInterface
{
    private const HIGH_AMOUNT_THRESHOLD = 5000; // USD
    private const WEIGHT = 40;

    public function evaluate(FraudContext $context): RuleResult
    {
        if ($context->currency === 'USD' && $context->amount > self::HIGH_AMOUNT_THRESHOLD) {
            return new RuleResult(
                triggered: true,
                score: self::WEIGHT,
                rule: 'high_amount',
                message: 'معاملة بمبلغ كبير: ' . $context->amount . ' USD'
            );
        }

        return new RuleResult(false, 0, '', '');
    }
}
```

### القاعدة 3: جهاز جديد

```php
class NewDeviceRule implements RuleInterface
{
    private const WEIGHT = 20;

    public function evaluate(FraudContext $context): RuleResult
    {
        $knownDevice = DeviceFingerprint::where('user_id', $context->user->id)
            ->where('fingerprint', $context->deviceId)
            ->exists();

        if (!$knownDevice && $context->deviceId) {
            return new RuleResult(
                triggered: true,
                score: self::WEIGHT,
                rule: 'new_device',
                message: 'جهاز جديد لم يسجل سابقاً'
            );
        }

        return new RuleResult(false, 0, '', '');
    }
}
```

### القاعدة 4: تحويلات متكررة لنفس الرقم

```php
class RepeatedTransferRule implements RuleInterface
{
    private const MAX_REPEATED = 3;
    private const WEIGHT = 25;

    public function evaluate(FraudContext $context): RuleResult
    {
        if ($context->toUser) {
            $count = Transaction::where('from_wallet_id', $context->fromWallet->id)
                ->where('to_wallet_id', $context->toUser->wallets()
                    ->where('currency', $context->currency)->first()?->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            if ($count >= self::MAX_REPEATED) {
                return new RuleResult(
                    triggered: true,
                    score: self::WEIGHT,
                    rule: 'repeated_transfers',
                    message: 'تحويلات متكررة لنفس المستخدم: ' . ($count + 1) . ' مرة اليوم'
                );
            }
        }

        return new RuleResult(false, 0, '', '');
    }
}
```
