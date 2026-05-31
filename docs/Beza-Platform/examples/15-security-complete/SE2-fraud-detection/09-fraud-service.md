# 09 - خدمة كشف الاحتيال (Fraud Service)

```php
<?php

namespace App\Services;

use App\Models\FlaggedTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FraudDetection\FraudContext;
use App\Services\FraudDetection\FraudResult;
use App\Services\FraudDetection\Rules\HighAmountRule;
use App\Services\FraudDetection\Rules\NewDeviceRule;
use App\Services\FraudDetection\Rules\RepeatedTransferRule;
use App\Services\FraudDetection\Rules\TransactionVelocityRule;

class FraudDetectionService
{
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            new TransactionVelocityRule(),
            new HighAmountRule(),
            new NewDeviceRule(),
            new RepeatedTransferRule(),
        ];
    }

    /**
     * تقييم معاملة للكشف عن الاحتيال
     */
    public function evaluate(
        User $user,
        Wallet $fromWallet,
        float $amount,
        string $currency,
        ?User $toUser = null,
        ?string $deviceId = null,
        string $ip = null
    ): FraudResult {
        $context = new FraudContext(
            user: $user,
            fromWallet: $fromWallet,
            amount: $amount,
            currency: $currency,
            toUser: $toUser,
            deviceId: $deviceId,
            ip: $ip ?? request()->ip(),
        );

        $totalScore = 0;
        $triggeredRules = [];

        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($context);
            if ($result->triggered) {
                $totalScore += $result->score;
                $triggeredRules[] = $result;
            }
        }

        return new FraudResult(
            score: min($totalScore, 100),
            triggeredRules: $triggeredRules,
        );
    }

    /**
     * تسجيل معاملة مشبوهة
     */
    public function flagTransaction(
        User $user,
        FraudResult $result,
        ?int $transactionId = null,
        float $amount = 0,
        string $currency = 'USD'
    ): FlaggedTransaction {
        return FlaggedTransaction::create([
            'transaction_id' => $transactionId,
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $currency,
            'triggered_rules' => array_map(fn($r) => [
                'rule' => $r->rule,
                'message' => $r->message,
                'score' => $r->score,
            ], $result->triggeredRules),
            'risk_score' => $result->score,
            'status' => 'pending',
        ]);
    }

    /**
     * هل يجب حظر المعاملة?
     */
    public function shouldBlock(FraudResult $result): bool
    {
        return $result->score >= 70;
    }

    /**
     * هل يجب طلب مراجعة?
     */
    public function shouldFlag(FraudResult $result): bool
    {
        return $result->score >= 40 && $result->score < 70;
    }
}
```
