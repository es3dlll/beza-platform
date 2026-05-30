<?php

declare(strict_types=1);

namespace Modules\Agent\Repositories;

use Illuminate\Support\Collection;
use Modules\Agent\Models\Agent;
use Modules\Agent\Models\AgentTransaction;

final class AgentRepository
{
    public function findById(string $id): ?Agent
    {
        return Agent::find($id);
    }

    public function findByUser(string $userId): ?Agent
    {
        return Agent::where('user_id', $userId)->first();
    }

    public function findByPhone(string $phone): ?Agent
    {
        return Agent::where('phone', $phone)->first();
    }

    public function findByGovernorate(string $governorate): Collection
    {
        return Agent::where('governorate', $governorate)
            ->whereIn('status', ['approved', 'active'])
            ->get();
    }

    public function findAllApproved(): Collection
    {
        return Agent::whereIn('status', ['approved', 'active'])->get();
    }

    public function save(Agent $agent): Agent
    {
        $agent->save();
        return $agent;
    }

    public function todayTotal(string $agentId, string $type): int
    {
        return (int) AgentTransaction::where('agent_id', $agentId)
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    public function saveTransaction(AgentTransaction $txn): AgentTransaction
    {
        $txn->save();
        return $txn;
    }
}
