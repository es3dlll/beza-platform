<?php

declare(strict_types=1);

namespace Modules\Fraud\Repositories;

use Modules\Fraud\Models\FraudRule;

class FraudRuleRepository
{
    public function findActiveByType(string $ruleType): iterable
    {
        return FraudRule::where('rule_type', $ruleType)
            ->where('is_active', true)
            ->get();
    }

    public function findAllActive(): iterable
    {
        return FraudRule::where('is_active', true)->get();
    }

    public function create(array $data): FraudRule
    {
        return FraudRule::create($data);
    }

    public function update(string $id, array $data): FraudRule
    {
        $rule = FraudRule::findOrFail($id);
        $rule->update($data);
        return $rule->fresh();
    }

    public function findById(string $id): ?FraudRule
    {
        return FraudRule::find($id);
    }
}
