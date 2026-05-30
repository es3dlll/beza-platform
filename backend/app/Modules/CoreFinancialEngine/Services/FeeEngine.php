<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Services;

use Modules\CoreFinancialEngine\Contracts\FeeEngineInterface;
use Modules\CoreFinancialEngine\DTOs\FeeAssessmentDto;
use Modules\CoreFinancialEngine\DTOs\FeeResultDto;
use Modules\CoreFinancialEngine\Events\FeeApplied;
use Modules\CoreFinancialEngine\Exceptions\FeeCalculationException;
use Modules\CoreFinancialEngine\Models\FeeRule;
use Modules\Ledger\DTOs\JournalLineDto;
use Modules\Ledger\DTOs\PostEntryDto;
use Modules\Ledger\Models\LedgerAccount;
use Modules\Ledger\Services\JournalService;

final class FeeEngine implements FeeEngineInterface
{
    public function __construct(
        private readonly JournalService $journal,
    ) {}

    private function resolveFeeAccountId(string $accountNumber): string
    {
        $account = LedgerAccount::where('account_number', $accountNumber)->first();
        return $account ? $account->id : $accountNumber;
    }

    public function calculate(FeeAssessmentDto $dto): FeeResultDto
    {
        $rule = FeeRule::where('fee_type', $dto->feeType)
            ->where('is_active', true)
            ->first();

        if (!$rule) {
            throw new FeeCalculationException("No active fee rule for type: {$dto->feeType}");
        }

        if ($rule->min_amount && $dto->transactionAmount < $rule->min_amount) {
            return new FeeResultDto(
                applied: false,
                feeAmount: 0,
                currency: $dto->currency,
                feeAccountId: $this->resolveFeeAccountId($rule->fee_account_number),
                feeRule: $dto->feeType,
            );
        }

        $feeAmount = match ($rule->calculation_type) {
            'flat' => $rule->value,
            'percentage' => (int) round($dto->transactionAmount * ($rule->value / 10000)),
            default => 0,
        };

        if ($rule->max_cap && $feeAmount > $rule->max_cap) {
            $feeAmount = $rule->max_cap;
        }

        return new FeeResultDto(
            applied: false,
            feeAmount: $feeAmount,
            currency: $dto->currency,
            feeAccountId: $this->resolveFeeAccountId($rule->fee_account_number),
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
