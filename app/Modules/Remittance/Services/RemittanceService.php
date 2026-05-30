<?php

declare(strict_types=1);

namespace Modules\Remittance\Services;

use Illuminate\Support\Str;
use Modules\Remittance\Models\RemittanceOrder;
use Modules\Remittance\DTOs\CreateRemittanceDto;
use Modules\Remittance\Enums\RemittanceStatus;
use Modules\Remittance\Repositories\RemittanceOrderRepository;
use Modules\Remittance\Repositories\CorridorRepository;
use Modules\Remittance\Repositories\BeneficiaryRepository;
use Modules\Remittance\Events\RemittanceOrderCreated;
use Modules\Remittance\Events\RemittanceOrderScreened;
use Modules\Remittance\Events\RemittanceOrderPaidIn;
use Modules\Remittance\Events\RemittanceOrderCompleted;
use Modules\Remittance\Events\RemittanceOrderFailed;
use Modules\Remittance\Events\RemittanceOrderRefunded;
use Modules\Remittance\Exceptions\RemittanceCorridorUnavailableException;
use Modules\Remittance\Exceptions\RemittanceBeneficiaryNotFoundException;
use Modules\Remittance\Exceptions\RemittanceBeneficiaryKycIncompleteException;
use Modules\Remittance\Exceptions\RemittanceLimitExceededException;
use Modules\Remittance\Exceptions\RemittancePurposeRequiredException;
use Modules\Remittance\Exceptions\RemittanceSourceOfFundsRequiredException;
use Modules\FX\Services\FxRateService;
use Modules\FX\Services\FxQuoteService;
use Modules\FX\Services\FxConversionService;
use Modules\FX\DTOs\GetQuoteDto;
use Modules\FX\DTOs\ExecuteConversionDto;

class RemittanceService
{
    private const PURPOSE_CODES = [
        'FAMILY_SUPPORT', 'SALARY', 'EDUCATION', 'MEDICAL', 'SAVINGS',
        'INVESTMENT', 'BUSINESS', 'CHARITY', 'OTHER',
    ];

    public function __construct(
        private readonly RemittanceOrderRepository $orderRepository,
        private readonly CorridorRepository $corridorRepository,
        private readonly BeneficiaryRepository $beneficiaryRepository,
        private readonly CorridorService $corridorService,
        private readonly BeneficiaryService $beneficiaryService,
        private readonly FxRateService $fxRateService,
        private readonly FxQuoteService $fxQuoteService,
        private readonly FxConversionService $fxConversionService,
    ) {}

    public function create(CreateRemittanceDto $dto): RemittanceOrder
    {
        $this->validatePurposeCode($dto->purposeCode);

        if ($dto->sourceOfFundsDeclaration === null || trim($dto->sourceOfFundsDeclaration) === '') {
            throw new RemittanceSourceOfFundsRequiredException;
        }

        $corridor = $this->corridorService->getActive($dto->senderCountry);

        $this->validateAmountLimits($dto->senderUserId, $dto->sourceAmount, $corridor);

        $beneficiary = $this->beneficiaryRepository->findById($dto->beneficiaryId);
        if (!$beneficiary) {
            throw new RemittanceBeneficiaryNotFoundException($dto->beneficiaryId);
        }
        $this->beneficiaryService->ensureKycCompleted($dto->beneficiaryId);

        $referenceNumber = $this->generateReferenceNumber();

        $order = $this->orderRepository->create($dto, $referenceNumber);

        $order->update(['status' => RemittanceStatus::SCREENING->value]);

        RemittanceOrderCreated::dispatch(
            $order->id,
            $corridor->id,
            $dto->senderUserId,
            $dto->beneficiaryId,
            $dto->sourceAmount,
            $dto->sourceCurrency,
            0,
            $dto->purposeCode,
        );

        return $order->fresh();
    }

    public function screen(string $orderId, bool $passed, ?string $caseId = null): RemittanceOrder
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new RemittanceBeneficiaryNotFoundException($orderId);
        }

        if ($order->status !== RemittanceStatus::SCREENING->value) {
            throw new \RuntimeException('Remittance order is not in screening status');
        }

        if ($passed) {
            $order = $this->orderRepository->updateStatus($orderId, RemittanceStatus::AWAITING_PAYMENT, [
                'compliance_result' => 'passed',
                'compliance_case_id' => $caseId,
            ]);
        } else {
            $order = $this->orderRepository->updateStatus($orderId, RemittanceStatus::SCREENING_FAILED, [
                'compliance_result' => 'failed',
                'compliance_case_id' => $caseId,
            ]);
        }

        RemittanceOrderScreened::dispatch($orderId, $passed ? 'passed' : 'failed', $caseId);

        return $order;
    }

    public function quote(string $orderId): RemittanceOrder
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new RemittanceBeneficiaryNotFoundException($orderId);
        }

        $corridor = $this->corridorRepository->findById($order->corridor_id);
        if (!$corridor || !$corridor->is_active) {
            throw new RemittanceCorridorUnavailableException($order->corridor_id);
        }

        $rate = $this->fxRateService->getActiveRate($order->source_currency, 'SYP', $corridor->fx_rate_source);

        $spreadMultiplier = 1.0 + ($corridor->fixed_spread_pct / 100.0);
        $adjustedRate = $rate->mid_rate * $spreadMultiplier;

        $targetAmount = (int) round($order->source_amount * $adjustedRate);

        $feeAmountInTarget = $this->calculateFee($order->source_amount, $targetAmount, $corridor);
        $feeAmountInSource = (int) round($feeAmountInTarget / $adjustedRate);

        $totalCost = $order->source_amount + $feeAmountInSource;

        $quoteDto = new GetQuoteDto(
            requestorId: $order->sender_user_id,
            requestorType: 'user',
            baseCurrency: $order->source_currency,
            quoteCurrency: 'SYP',
            amount: $order->source_amount,
            rateType: $corridor->fx_rate_source,
        );

        $quote = $this->fxQuoteService->generate($quoteDto);

        $order = $this->orderRepository->updateQuoteDetails(
            $orderId,
            $targetAmount,
            $adjustedRate,
            $feeAmountInSource,
            $feeAmountInTarget,
            $totalCost,
            $quote->id,
        );

        return $order;
    }

    public function confirmPaidIn(string $orderId, int $amountPaid): RemittanceOrder
    {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            throw new RemittanceBeneficiaryNotFoundException($orderId);
        }

        $order = $this->orderRepository->updateStatus($orderId, RemittanceStatus::PAID_IN, [
            'paid_in_at' => now(),
        ]);

        $this->fxConversionService->execute(
            new ExecuteConversionDto(
                quoteId: $order->fx_quote_id,
                fromWalletId: $order->sender_user_id,
            ),
        );

        $order = $this->orderRepository->updateStatus($orderId, RemittanceStatus::PROCESSING);

        RemittanceOrderPaidIn::dispatch($orderId, $amountPaid);

        return $order;
    }

    public function complete(string $orderId): RemittanceOrder
    {
        $order = $this->orderRepository->updateStatus($orderId, RemittanceStatus::COMPLETED, [
            'completed_at' => now(),
        ]);

        RemittanceOrderCompleted::dispatch(
            $orderId,
            $order->beneficiary_id,
            $order->target_amount - $order->fee_amount_in_target,
        );

        return $order;
    }

    public function fail(string $orderId, string $reason): RemittanceOrder
    {
        $order = $this->orderRepository->updateStatus($orderId, RemittanceStatus::FAILED, [
            'failure_reason' => $reason,
        ]);

        RemittanceOrderFailed::dispatch($orderId, $reason);

        return $order;
    }

    public function refund(string $orderId, string $reason): RemittanceOrder
    {
        $order = $this->orderRepository->updateStatus($orderId, RemittanceStatus::REFUNDED, [
            'refund_reason' => $reason,
        ]);

        RemittanceOrderRefunded::dispatch($orderId, $order->source_amount, $reason);

        return $order;
    }

    public function findById(string $id): ?RemittanceOrder
    {
        return $this->orderRepository->findById($id);
    }

    public function findBySender(string $userId, int $perPage = 15): iterable
    {
        return $this->orderRepository->findBySender($userId, $perPage);
    }

    private function validatePurposeCode(string $code): void
    {
        if (!in_array($code, self::PURPOSE_CODES, true)) {
            throw new RemittancePurposeRequiredException;
        }
    }

    private function validateAmountLimits(string $userId, int $amount, $corridor): void
    {
        if ($amount < $corridor->min_amount) {
            throw new RemittanceLimitExceededException('min_amount', $amount, $corridor->min_amount);
        }

        if ($amount > $corridor->max_amount) {
            throw new RemittanceLimitExceededException('max_amount', $amount, $corridor->max_amount);
        }

        $dailyTotal = $this->orderRepository->getDailyTotalsForSender($userId);
        if (($dailyTotal + $amount) > $corridor->daily_limit_per_sender) {
            throw new RemittanceLimitExceededException('daily', $amount, $corridor->daily_limit_per_sender - $dailyTotal);
        }

        $monthlyTotal = $this->orderRepository->getMonthlyTotalsForSender($userId);
        if (($monthlyTotal + $amount) > $corridor->monthly_limit_per_sender) {
            throw new RemittanceLimitExceededException('monthly', $amount, $corridor->monthly_limit_per_sender - $monthlyTotal);
        }
    }

    private function calculateFee(int $sourceAmount, int $targetAmount, $corridor): int
    {
        return match ($corridor->fee_type) {
            'percentage' => (int) round($targetAmount * ($corridor->fixed_spread_pct / 100.0)),
            'fixed' => $corridor->fee_structure['amount'] ?? 0,
            'tiered' => $this->calculateTieredFee($sourceAmount, $corridor->fee_structure ?? []),
            default => 0,
        };
    }

    private function calculateTieredFee(int $amount, array $tiers): int
    {
        $fee = 0;
        foreach ($tiers as $tier) {
            if ($amount >= ($tier['from'] ?? 0) && $amount <= ($tier['to'] ?? PHP_INT_MAX)) {
                $fee = (int) ($tier['fee'] ?? 0);
                break;
            }
        }
        return $fee;
    }

    private function generateReferenceNumber(): string
    {
        return 'REM-' . strtoupper(Str::random(8));
    }
}
