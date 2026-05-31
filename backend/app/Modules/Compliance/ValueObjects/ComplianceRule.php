<?php

declare(strict_types=1);

namespace App\Modules\Compliance\ValueObjects;

final readonly class ComplianceRule
{
    public function __construct(
        private string $id,
        private string $description,
        private string $evaluationType,
        private int $threshold,
        private string $action,
    ) {}

    const EVAL_TYPES = ['velocity', 'geography', 'device', 'amount', 'recipient_pattern'];
    const ACTIONS = ['monitor', 'suspend', 'block', 'manual_review'];

    public function evaluate(array $context): int
    {
        $score = match ($this->evaluationType) {
            'velocity' => $this->evalVelocity($context),
            'amount' => $this->evalAmount($context),
            'device' => $this->evalDevice($context),
            'geography' => $this->evalGeography($context),
            'recipient_pattern' => $this->evalRecipient($context),
            default => 0,
        };

        return min($score, 100);
    }

    private function evalVelocity(array $ctx): int
    {
        $count = $ctx['daily_transaction_count'] ?? 0;
        return $count >= $this->threshold ? (int) min(($count / $this->threshold) * 50, 90) : 0;
    }

    private function evalAmount(array $ctx): int
    {
        $amount = $ctx['amount'] ?? 0;
        return $amount >= $this->threshold ? (int) min(($amount / $this->threshold) * 30, 80) : 0;
    }

    private function evalDevice(array $ctx): int
    {
        $new = $ctx['is_new_device'] ?? false;
        $untrusted = $ctx['is_untrusted_device'] ?? false;
        return $new ? 40 : ($untrusted ? 70 : 0);
    }

    private function evalGeography(array $ctx): int
    {
        $highRisk = $ctx['is_high_risk_country'] ?? false;
        return $highRisk ? 65 : 0;
    }

    private function evalRecipient(array $ctx): int
    {
        $newRecipient = $ctx['is_new_recipient'] ?? false;
        $repeatAmount = $ctx['recipient_repeat_amount'] ?? 0;
        return $newRecipient ? 25 : ($repeatAmount > 3 ? 50 : 0);
    }

    public function id(): string { return $this->id; }
    public function action(): string { return $this->action; }
    public function evaluationType(): string { return $this->evaluationType; }
    public function threshold(): int { return $this->threshold; }
    public function description(): string { return $this->description; }
}
