<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentCommission;
use App\Modules\Agent\Models\AgentSettlement;
use App\Modules\Agent\Models\FraudAlert;
use App\Modules\Agent\Models\LedgerEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AgentAdminService
{
    public function listAgents(array $filters = []): LengthAwarePaginator
    {
        $query = Agent::with('user:id,name,email,phone');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('license_number', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['balance_min'])) {
            $query->where('balance', '>=', (int) $filters['balance_min']);
        }

        if (! empty($filters['balance_max'])) {
            $query->where('balance', '<=', (int) $filters['balance_max']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getAgentDetail(int $id): Agent
    {
        $agent = Agent::with([
            'user:id,name,email,phone,status',
            'commissions' => function ($q) {
                $q->where('status', 'accrued')->latest()->limit(20);
            },
            'transactions' => function ($q) {
                $q->latest()->limit(20);
            },
        ])->findOrFail($id);

        return $agent;
    }

    public function getAgentCommissions(int $id, array $filters = []): LengthAwarePaginator
    {
        Agent::findOrFail($id);

        $query = AgentCommission::where('agent_id', $id)
            ->with('transaction:id,type,amount,currency,created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function approveCommission(int $id, User $admin, ?string $note = null): AgentCommission
    {
        return DB::transaction(function () use ($id, $admin, $note) {
            $commission = AgentCommission::where('id', $id)
                ->where('status', 'accrued')
                ->lockForUpdate()
                ->first();

            if (! $commission) {
                throw new \RuntimeException('Commission is not in accrued status');
            }

            $agent = Agent::where('id', $commission->agent_id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = $agent->balance;

            $commission->update([
                'status' => 'settled',
                'settled_at' => now(),
            ]);

            $agent->increment('balance', $commission->amount);

            LedgerEntry::create([
                'ledgerable_id' => $commission->id,
                'ledgerable_type' => AgentCommission::class,
                'agent_id' => $agent->id,
                'admin_id' => $admin->id,
                'type' => 'commission_approval',
                'amount' => $commission->amount,
                'currency' => $commission->currency,
                'direction' => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $commission->amount,
                'notes' => $note ?? 'Commission approved by admin',
            ]);

            return $commission->fresh();
        });
    }

    public function getAgentSettlements(int $id, array $filters = []): LengthAwarePaginator
    {
        Agent::findOrFail($id);

        $query = AgentSettlement::where('agent_id', $id);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function approveSettlement(int $id, User $admin, ?string $note = null): AgentSettlement
    {
        return DB::transaction(function () use ($id, $admin, $note) {
            $settlement = AgentSettlement::where('id', $id)
                ->whereIn('status', ['pending', 'completed'])
                ->lockForUpdate()
                ->first();

            if (! $settlement) {
                throw new \RuntimeException('Settlement cannot be approved in its current status');
            }

            $agent = Agent::where('id', $settlement->agent_id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = $agent->balance;

            $settlement->update([
                'status' => 'approved',
                'processed_at' => now(),
            ]);

            $agent->increment('balance', $settlement->net_amount);

            LedgerEntry::create([
                'ledgerable_id' => $settlement->id,
                'ledgerable_type' => AgentSettlement::class,
                'agent_id' => $agent->id,
                'admin_id' => $admin->id,
                'type' => 'settlement_approval',
                'amount' => $settlement->net_amount,
                'currency' => $settlement->currency,
                'direction' => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $settlement->net_amount,
                'notes' => $note ?? 'Settlement approved by admin',
            ]);

            return $settlement->fresh();
        });
    }

    public function getFraudAlerts(array $filters = []): LengthAwarePaginator
    {
        $query = FraudAlert::with(['agent:id,business_name,region', 'resolver:id,name']);

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function resolveFraudAlert(int $id, User $admin, string $action, ?string $note = null): FraudAlert
    {
        return DB::transaction(function () use ($id, $admin, $action, $note) {
            $alert = FraudAlert::where('id', $id)
                ->whereIn('status', ['open', 'investigating'])
                ->lockForUpdate()
                ->first();

            if (! $alert) {
                throw new \RuntimeException('Alert is not in resolvable status');
            }

            $alert->update([
                'status' => 'resolved',
                'resolution' => $note ?: "Resolved: {$action}",
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            return $alert->fresh();
        });
    }
}
