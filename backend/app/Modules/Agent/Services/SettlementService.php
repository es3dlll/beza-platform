<?php

declare(strict_types=1);

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Events\SettlementDue;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentTransaction;
use App\Modules\Agent\Models\Settlement;
use App\Modules\FinancialCore\Services\TransactionService;
use Illuminate\Support\Facades\DB;

final class SettlementService
{
    public function __construct(
        private readonly AgentService $agentService,
        private readonly TransactionService $transactionService,
    ) {}

    public function generateDailySettlements(string $date): array
    {
        $agents = Agent::where('status', 'active')->get();
        $results = [];

        foreach ($agents as $agent) {
            $result = $this->generateForAgent($agent, $date);
            $results[] = $result;
        }

        return $results;
    }

    public function generateForAgent(Agent $agent, string $date): Settlement
    {
        $transactions = AgentTransaction::where('agent_id', $agent->id)
            ->whereDate('settlement_date', $date)
            ->get();

        $cashInTotal = $transactions->where('type', 'cash_in')->sum('amount');
        $cashOutTotal = $transactions->where('type', 'cash_out')->sum('amount');
        $commissionTotal = $transactions->sum('commission_amount');

        $netExpected = $cashInTotal - $cashOutTotal;
        $wallet = $this->agentService->getWallet($agent->id);
        $actual = $wallet->float_balance;

        return DB::transaction(function () use ($agent, $date, $netExpected, $actual, $commissionTotal) {
            $existing = Settlement::where('agent_id', $agent->id)
                ->where('settlement_date', $date)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $settlement = Settlement::create([
                'agent_id' => $agent->id,
                'settlement_date' => $date,
                'expected_amount' => $netExpected,
                'actual_amount' => $actual,
                'difference' => $netExpected - $actual,
                'commission_amount' => $commissionTotal,
                'status' => $netExpected === $actual ? 'confirmed' : 'pending',
                'settled_at' => $netExpected === $actual ? now() : null,
            ]);

            if ($netExpected !== $actual) {
                event(new SettlementDue(
                    agentId: $agent->id,
                    settlementDate: $date,
                    expectedAmount: $netExpected,
                    commissionAmount: $commissionTotal,
                ));
            }

            return $settlement;
        });
    }

    public function getSettlements(string $agentId, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Settlement::where('agent_id', $agentId)
            ->orderBy('settlement_date', 'desc')
            ->paginate($perPage);
    }
}
