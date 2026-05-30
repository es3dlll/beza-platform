<?php

declare(strict_types=1);

namespace Modules\Savings\Repositories;

use Modules\Savings\Models\SavingsProfitRule;

final class SavingsProfitRuleRepository
{
    public function findActive(): ?SavingsProfitRule
    {
        return SavingsProfitRule::where('is_active', true)->first();
    }

    public function findById(string $id): ?SavingsProfitRule
    {
        return SavingsProfitRule::find($id);
    }

    public function create(array $data): SavingsProfitRule
    {
        return SavingsProfitRule::create($data);
    }
}
