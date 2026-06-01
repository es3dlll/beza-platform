# 10 - محرك قواعد الكشف (Detection Rules Engine)

## FraudContext

```php
<?php

namespace App\Services\FraudDetection;

use App\Models\User;
use App\Models\Wallet;

class FraudContext
{
    public function __construct(
        public readonly User $user,
        public readonly Wallet $fromWallet,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?User $toUser = null,
        public readonly ?string $deviceId = null,
        public readonly string $ip = '',
    ) {}
}
```

## RuleResult

```php
class RuleResult
{
    public function __construct(
        public readonly bool $triggered,
        public readonly int $score,
        public readonly string $rule,
        public readonly string $message,
    ) {}
}
```

## FraudResult

```php
class FraudResult
{
    public function __construct(
        public readonly int $score,
        public readonly array $triggeredRules,
    ) {}

    public function getRiskLevel(): string
    {
        return match (true) {
            $this->score >= 70 => 'high',
            $this->score >= 40 => 'medium',
            $this->score >= 20 => 'low',
            default => 'none',
        };
    }

    public function hasTriggers(): bool
    {
        return count($this->triggeredRules) > 0;
    }
}
```

## التكامل مع TransferService

```php
// في TransferService
public function transfer(...): array
{
    // ... التحقق من PIN والرصيد ...

    // تقييم الاحتيال
    $fraudResult = app(FraudDetectionService::class)->evaluate(
        user: $fromUser,
        fromWallet: $fromWallet,
        amount: $amount,
        currency: $currency,
        toUser: $toUser,
        deviceId: request()->header('X-Device-ID'),
        ip: request()->ip(),
    );

    if ($fraudResult->shouldBlock()) {
        $this->fraudService->flagTransaction(
            $fromUser, $fraudResult, null, $amount, $currency
        );
        throw new TransactionBlockedByFraudDetectionException();
    }

    if ($fraudResult->shouldFlag()) {
        // تنفيذ المعاملة ولكن تعليمها للمراجعة
        $transaction = $this->executeTransfer(...);
        $this->fraudService->flagTransaction(
            $fromUser, $fraudResult, $transaction->id, $amount, $currency
        );
        // إشعار المشرف
        event(new TransactionFlagged($transaction, $fraudResult));
    } else {
        $transaction = $this->executeTransfer(...);
    }

    return ['transaction' => $transaction, 'new_balance' => ...];
}
```
