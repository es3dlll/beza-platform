<?php

namespace Modules\CoreFinancialEngine\Services;

use Modules\CoreFinancialEngine\Contracts\FeeEngineInterface;
use Modules\CoreFinancialEngine\DTOs\FeeAssessmentDto;
use Modules\CoreFinancialEngine\DTOs\FeeResultDto;
use Modules\CoreFinancialEngine\Events\FeeApplied;
use Modules\CoreFinancialEngine\Exceptions\FeeCalculationException;
use App\Modules\Ledger\DTOs\JournalLineDto;
use App\Modules\Ledger\DTOs\PostEntryDto;
use App\Modules\Ledger\Services\JournalService;

final class FeeEngine implements FeeEngineInterface
{
    private const FEE_RULES = [
        'transfer_out' => ['type' => 'flat', 'value' => 500, 'account' => '4000-001'],    // تحويل خارجي
        'transfer_in' => ['type' => 'flat', 'value' => 0, 'account' => '4000-001'],
        'cash_withdrawal' => ['type' => 'percentage', 'value' => 0.5, 'account' => '4000-002'],
        'cash_deposit' => ['type' => 'flat', 'value' => 0, 'account' => '4000-002'],
        'bill_payment' => ['type' => 'flat', 'value' => 200, 'account' => '4000-003'],
        'wallet_to_wallet' => ['type' => 'flat', 'value' => 0, 'account' => '4000-004'],
        'agent_cash_out' => ['type' => 'percentage', 'value' => 1.0, 'account' => '4000-005'],
        'agent_cash_in' => ['type' => 'flat', 'value' => 0, 'account' => '4000-005'],
    ];

    private const REVENUE_ACCOUNT = '4000-000';

    public function __construct(
        private readonly JournalService $journal,
    ) {}

    public function calculate(FeeAssessmentDto $dto): FeeResultDto
    {
        $rule = self::FEE_RULES[$dto->feeType] ?? null;
        if (!$rule) {
            throw new FeeCalculationException("No fee rule for type: {$dto->feeType}");
        }

        $feeAmount = match ($rule['type']) {
            'flat' => $rule['value'],
            'percentage' => (int) round($dto->transactionAmount * ($rule['value'] / 100)),
            default => 0,
        };

        return new FeeResultDto(
            applied: false,
            feeAmount: $feeAmount,
            currency: $dto->currency,
            feeAccountId: $rule['account'],
            feeRule: $dto->feeType,
        );
    }

    public function apply(FeeAssessmentDto $dto): FeeResultDto
    {
        $calculated = $this->calculate($dto);
        if ($calculated->feeAmount <= 0) {
            return $calculated;
        }

        $entryDto = new PostEntryDto(
            referenceType: 'fee',
            referenceId: $dto->referenceId ?? uniqid('fee_', true),
            description: "Fee: {$dto->feeType}",
            lines: [
                new JournalLineDto($dto->accountId, $calculated->feeAmount, 'debit', "Fee charge: {$dto->feeType}"),
                new JournalLineDto($calculated->feeAccountId, $calculated->feeAmount, 'credit', "Fee revenue: {$dto->feeType}"),
            ],
        );

        try {
            $entry = $this->journal->post($entryDto);

            event(new FeeApplied(
                feeType: $dto->feeType,
                accountId: $dto->accountId,
                feeAmount: $calculated->feeAmount,
                currency: $dto->currency,
                journalEntryId: $entry->id,
            ));

            return new FeeResultDto(
                applied: true,
                feeAmount: $calculated->feeAmount,
                currency: $dto->currency,
                feeAccountId: $calculated->feeAccountId,
                journalEntryId: $entry->id,
                feeRule: $dto->feeType,
            );
        } catch (\Exception $e) {
            return new FeeResultDto(
                applied: false,
                feeAmount: $calculated->feeAmount,
                currency: $dto->currency,
                feeAccountId: $calculated->feeAccountId,
                error: $e->getMessage(),
            );
        }
    }
}
