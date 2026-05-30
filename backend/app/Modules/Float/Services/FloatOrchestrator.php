<?php

declare(strict_types=1);

namespace Modules\Float\Services;

use Modules\Float\DTOs\CreateFloatAccountDto;
use Modules\Float\DTOs\FloatTransactionDto;
use Modules\Float\Models\FloatAccount;

final class FloatOrchestrator
{
    public function __construct(
        private readonly FloatService $float,
    ) {}

    public function ensureAgentAccounts(string $agentId, string $currency = 'SYP'): array
    {
        $cash = $this->float->create(new CreateFloatAccountDto(
            ownerType: 'agent',
            ownerId: $agentId,
            floatType: 'cash',
            currency: $currency,
            minimumBalance: 0,
        ));

        $electronic = $this->float->create(new CreateFloatAccountDto(
            ownerType: 'agent',
            ownerId: $agentId,
            floatType: 'electronic',
            currency: $currency,
            minimumBalance: 0,
        ));

        return ['cash' => $cash, 'electronic' => $electronic];
    }

    public function processCashIn(string $agentId, int $amount): void
    {
        $floats = $this->ensureAgentAccounts($agentId);
        $cashAccount = $floats['cash'];
        $electronicAccount = $floats['electronic'];

        $this->float->credit(new FloatTransactionDto(
            floatAccountId: $cashAccount->id,
            type: 'cash_in',
            amount: $amount,
            description: 'Agent cash-in: customer deposited cash',
        ));

        $this->float->debit(new FloatTransactionDto(
            floatAccountId: $electronicAccount->id,
            type: 'cash_in_electronic',
            amount: $amount,
            description: 'Agent cash-in: electronic float decreased',
        ));
    }

    public function processCashOut(string $agentId, int $amount): void
    {
        $floats = $this->ensureAgentAccounts($agentId);
        $cashAccount = $floats['cash'];
        $electronicAccount = $floats['electronic'];

        $this->float->debit(new FloatTransactionDto(
            floatAccountId: $cashAccount->id,
            type: 'cash_out',
            amount: $amount,
            description: 'Agent cash-out: customer withdrew cash',
        ));

        $this->float->credit(new FloatTransactionDto(
            floatAccountId: $electronicAccount->id,
            type: 'cash_out_electronic',
            amount: $amount,
            description: 'Agent cash-out: electronic float increased',
        ));
    }

    public function getLiquidityScore(string $agentId): array
    {
        $floats = $this->getBalancesForOwner('agent', $agentId);

        $cashBalance = 0;
        $electronicBalance = 0;

        foreach ($floats as $f) {
            if ($f['float_type'] === 'cash') $cashBalance = $f['available'];
            if ($f['float_type'] === 'electronic') $electronicBalance = $f['available'];
        }

        $total = $cashBalance + $electronicBalance;
        $cashRatio = $total > 0 ? ($cashBalance / $total) * 100 : 0;
        $score = match(true) {
            $cashRatio >= 30 && $cashRatio <= 70 => 100,
            $cashRatio >= 20 && $cashRatio <= 80 => 80,
            $cashRatio >= 10 && $cashRatio <= 90 => 60,
            default => 40,
        };

        return [
            'cash_balance' => $cashBalance,
            'electronic_balance' => $electronicBalance,
            'total_liquidity' => $total,
            'cash_ratio' => round($cashRatio, 1),
            'liquidity_score' => $score,
        ];
    }

    public function getBalancesForOwner(string $ownerType, string $ownerId): array
    {
        $accounts = \Modules\Float\Repositories\FloatRepository::class;
        $repo = app($accounts);
        return $repo->findByOwner($ownerType, $ownerId)->map(fn($a) => [
            'id' => $a->id,
            'float_type' => $a->float_type,
            'balance' => $a->balance,
            'available' => $a->availableBalance(),
        ])->toArray();
    }
}
