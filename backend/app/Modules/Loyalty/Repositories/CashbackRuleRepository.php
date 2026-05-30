<?php

declare(strict_types=1);

namespace Modules\Loyalty\Repositories;

use Modules\Loyalty\Models\CashbackRule;

class CashbackRuleRepository
{
    public function findActiveByTrigger(string $triggerType): iterable
    {
        return CashbackRule::where('trigger_type', $triggerType)
            ->where('is_active', true)
            ->get();
    }

    public function findAllActive(): iterable
    {
        return CashbackRule::where('is_active', true)->get();
    }

    public function create(array $data): CashbackRule
    {
        return CashbackRule::create($data);
    }
}
