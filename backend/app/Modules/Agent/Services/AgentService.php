<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Exceptions\AgentNotFoundException;
use App\Modules\Agent\Exceptions\AgentNotActiveException;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentWallet;

final class AgentService
{
    public function register(array $data): Agent
    {
        return Agent::create($data);
    }

    public function getAgent(string $id): Agent
    {
        return Agent::findOrFail($id);
    }

    public function getAgentByPhone(string $phone): Agent
    {
        return Agent::where('phone', $phone)->firstOrFail();
    }

    public function getWallet(string $agentId, string $currency = 'SYP'): AgentWallet
    {
        $agent = $this->getAgent($agentId);
        return $agent->wallets()->where('currency', $currency)->firstOrFail();
    }

    public function verify(string $agentId): void
    {
        $agent = $this->getAgent($agentId);
        $agent->update(['is_verified' => true, 'verified_at' => now(), 'status' => 'active']);
    }

    public function suspend(string $agentId): void
    {
        $this->getAgent($agentId)->update(['status' => 'suspended']);
    }

    public function activateWallet(string $agentId, string $currency = 'SYP'): AgentWallet
    {
        $agent = $this->getAgent($agentId);
        $wallet = new AgentWallet();
        $wallet->id = \Illuminate\Support\Str::ulid()->toBase32();
        $wallet->agent_id = $agent->id;
        $wallet->currency = $currency;
        $wallet->balance = 0;
        $wallet->float_balance = 0;
        $wallet->status = 'active';
        $wallet->save();
        return $wallet;
    }

    public function assertCanTransact(string $agentId): void
    {
        $agent = $this->getAgent($agentId);
        if (!$agent->canTransact()) {
            throw new AgentNotActiveException("Agent {$agentId} is not active or verified");
        }
    }
}
