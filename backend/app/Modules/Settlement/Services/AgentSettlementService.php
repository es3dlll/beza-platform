<?php

declare(strict_types=1);

namespace Modules\Settlement\Services;

use Modules\Settlement\DTOs\CreateSettlementDto;
use Modules\Settlement\Models\Settlement;

final class AgentSettlementService
{
    public function __construct(
        private readonly SettlementService $settlements,
    ) {}

    public function settleAgentCommission(string $agentId, int $totalCommission, \DateTimeInterface $periodStart, \DateTimeInterface $periodEnd): Settlement
    {
        $settlement = $this->settlements->create(new CreateSettlementDto(
            referenceType: 'agent_commission',
            referenceId: "agent-{$agentId}-{$periodStart->format('Ymd')}",
            settlementType: 'agent_commission',
            grossAmount: $totalCommission,
            feeAmount: 0,
            commissionAmount: 0,
            currency: 'SYP',
            settlementAccountId: $agentId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            metadata: ['agent_id' => $agentId, 'type' => 'commission'],
        ));

        $this->settlements->addLine(
            $settlement->id,
            '4000-000',
            $totalCommission,
            'debit',
            'Commission expense',
        );

        $this->settlements->addLine(
            $settlement->id,
            $agentId,
            $totalCommission,
            'credit',
            'Agent commission',
        );

        return $settlement;
    }

    public function settleDailyAgentNet(string $agentId, int $cashInTotal, int $cashOutTotal, int $commissionTotal): Settlement
    {
        $netAmount = $cashInTotal - $cashOutTotal - $commissionTotal;

        $settlement = $this->settlements->create(new CreateSettlementDto(
            referenceType: 'agent_daily',
            referenceId: "agent-{$agentId}-" . now()->format('Ymd'),
            settlementType: 'agent_daily_net',
            grossAmount: $cashInTotal,
            feeAmount: 0,
            commissionAmount: $commissionTotal,
            currency: 'SYP',
            settlementAccountId: '9000-SETTLE',
            periodStart: now()->startOfDay(),
            periodEnd: now()->endOfDay(),
            metadata: ['agent_id' => $agentId, 'cash_in' => $cashInTotal, 'cash_out' => $cashOutTotal],
        ));

        if ($netAmount > 0) {
            $this->settlements->addLine($settlement->id, $agentId, $netAmount, 'debit', 'Agent daily net settlement');
            $this->settlements->addLine($settlement->id, '9000-SETTLE', $netAmount, 'credit', 'Settlement account');
        } elseif ($netAmount < 0) {
            $absNet = abs($netAmount);
            $this->settlements->addLine($settlement->id, '9000-SETTLE', $absNet, 'debit', 'Settlement account');
            $this->settlements->addLine($settlement->id, $agentId, $absNet, 'credit', 'Agent daily net settlement');
        }

        return $settlement;
    }
}
