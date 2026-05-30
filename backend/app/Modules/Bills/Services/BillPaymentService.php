<?php

declare(strict_types=1);

namespace Modules\Bills\Services;

use Illuminate\Support\Str;
use Modules\Bills\Models\BillPayment;
use Modules\Bills\DTOs\BillInquiryDto;
use Modules\Bills\DTOs\PayBillDto;
use Modules\Bills\Enums\BillPaymentStatus;
use Modules\Bills\Repositories\BillPaymentRepository;
use Modules\Bills\Repositories\BillProviderRepository;
use Modules\Bills\Events\BillInquiryCompleted;
use Modules\Bills\Events\BillPaid;
use Modules\Bills\Events\BillPaymentFailed;
use Modules\Bills\Events\BillRefunded;
use Modules\Bills\Exceptions\BillNotFoundException;
use Modules\Bills\Exceptions\BillAlreadyPaidException;
use Modules\Bills\Exceptions\BillPaymentFailedException;
use Modules\Bills\Exceptions\BillInquiryFailedException;
use Modules\Bills\Exceptions\BillInvalidAmountException;
use Modules\Bills\Exceptions\BillAccountFormatInvalidException;
use Modules\Bills\Exceptions\BillRetryExceededException;

final class BillPaymentService
{
    private const MAX_RETRIES = 3;
    private const RETRY_WINDOW_MINUTES = 15;

    public function __construct(
        private readonly BillPaymentRepository $paymentRepository,
        private readonly BillProviderRepository $providerRepository,
        private readonly BillProviderService $providerService,
    ) {}

    public function inquire(BillInquiryDto $dto): BillPayment
    {
        $provider = $this->providerService->findById($dto->billProviderId);
        if (!$provider) {
            throw new BillNotFoundException("provider: {$dto->billProviderId}");
        }

        if ($provider->account_format_regex) {
            if (!preg_match("/{$provider->account_format_regex}/", $dto->accountNumber)) {
                throw new BillAccountFormatInvalidException(
                    $dto->accountNumber,
                    $provider->account_format_regex,
                );
            }
        }

        $simulatedAmount = $this->simulateBillerInquiry($provider, $dto->accountNumber);

        $payment = $this->paymentRepository->create([
            'user_id' => $dto->userId,
            'bill_provider_id' => $provider->id,
            'account_number' => $dto->accountNumber,
            'account_name' => "Account {$dto->accountNumber}",
            'period' => now()->format('Y-m'),
            'due_date' => now()->addDays(15),
            'amount_due' => $simulatedAmount,
            'status' => BillPaymentStatus::INQUIRED->value,
        ]);

        BillInquiryCompleted::dispatch(
            $payment->id,
            $dto->userId,
            $provider->id,
            $dto->accountNumber,
            $simulatedAmount,
        );

        return $payment;
    }

    public function pay(PayBillDto $dto): BillPayment
    {
        $payment = $this->paymentRepository->findById($dto->billPaymentId);
        if (!$payment) {
            throw new BillNotFoundException("payment: {$dto->billPaymentId}");
        }

        if ($payment->status === BillPaymentStatus::PAID->value) {
            throw new BillAlreadyPaidException($dto->billPaymentId);
        }

        if ($payment->status !== BillPaymentStatus::INQUIRED->value) {
            throw new BillPaymentFailedException("Invalid status: {$payment->status}");
        }

        $retryCount = $this->paymentRepository->countRecentRetries(
            $payment->user_id,
            $payment->bill_provider_id,
            self::RETRY_WINDOW_MINUTES,
        );
        if ($retryCount >= self::MAX_RETRIES) {
            throw new BillRetryExceededException($payment->account_number);
        }

        $provider = $this->providerService->findById($payment->bill_provider_id);

        if ($dto->amount > 0 && $dto->amount !== $payment->amount_due) {
            throw new BillInvalidAmountException($payment->amount_due, $dto->amount);
        }

        $amount = $dto->amount > 0 ? $dto->amount : $payment->amount_due;

        $feeAmount = $this->calculateFee($amount, $provider);
        $totalDebited = $amount + $feeAmount;

        $simulatedSuccess = $this->simulateBillerPayment($provider, $payment->account_number, $amount);

        if (!$simulatedSuccess) {
            $this->paymentRepository->update($payment->id, [
                'status' => BillPaymentStatus::FAILED->value,
                'failure_reason' => 'Biller rejected the payment',
                'retry_count' => $payment->retry_count + 1,
                'last_retry_at' => now(),
            ]);

            BillPaymentFailed::dispatch($payment->id, 'Biller rejected the payment');

            throw new BillPaymentFailedException;
        }

        $payment = $this->paymentRepository->update($payment->id, [
            'amount_paid' => $amount,
            'fee_amount' => $feeAmount,
            'total_debited' => $totalDebited,
            'status' => BillPaymentStatus::PAID->value,
            'paid_at' => now(),
            'biller_reference' => strtoupper(Str::random(12)),
        ]);

        BillPaid::dispatch($payment->id, $payment->user_id, $payment->bill_provider_id, $amount, $feeAmount);

        return $payment;
    }

    public function refund(string $paymentId, string $reason): BillPayment
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if (!$payment) {
            throw new BillNotFoundException("payment: {$paymentId}");
        }

        $payment = $this->paymentRepository->update($paymentId, [
            'status' => BillPaymentStatus::REFUNDED->value,
            'refund_reason' => $reason,
        ]);

        BillRefunded::dispatch($paymentId, $payment->amount_paid, $reason);

        return $payment;
    }

    public function findByUser(string $userId, int $perPage = 15): iterable
    {
        return $this->paymentRepository->findByUser($userId, $perPage);
    }

    public function findById(string $id): ?BillPayment
    {
        return $this->paymentRepository->findById($id);
    }

    private function calculateFee(int $amount, ?\Modules\Bills\Models\BillProvider $provider): int
    {
        if (!$provider) return 0;
        $fee = (int) round($amount * ($provider->fee_percentage / 100));
        return max($provider->fee_min_syp, min($fee, $provider->fee_max_syp));
    }

    private function simulateBillerInquiry($provider, string $accountNumber): int
    {
        $base = match ($provider->category) {
            'telecom' => 15000,
            'utility' => 25000,
            'government' => 50000,
            default => 10000,
        };
        return $base + random_int(0, 5000);
    }

    private function simulateBillerPayment($provider, string $account, int $amount): bool
    {
        return true;
    }
}
