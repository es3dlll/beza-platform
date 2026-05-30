<?php

declare(strict_types=1);

namespace Modules\Merchant\Services;

use Modules\Merchant\Models\MerchantPayment;
use Modules\Merchant\DTOs\MerchantPaymentDto;
use Modules\Merchant\Enums\MerchantPaymentStatus;
use Modules\Merchant\Repositories\MerchantPaymentRepository;
use Modules\Merchant\Repositories\MerchantRepository;
use Modules\Merchant\Exceptions\MerchantNotFoundException;
use Modules\Merchant\Exceptions\MerchantSuspendedException;
use Modules\Merchant\Exceptions\MerchantPaymentAboveMaximumException;
use Modules\Merchant\Exceptions\MerchantRefundExpiredException;
use Modules\Merchant\Events\MerchantPaymentCompleted;

final class MerchantPaymentService implements \Modules\Merchant\Contracts\MerchantPaymentServiceInterface
{
    private const MIN_AMOUNT = 500;
    private const REFUND_DAYS = 7;

    public function __construct(
        private readonly MerchantPaymentRepository $paymentRepository,
        private readonly MerchantRepository $merchantRepository,
        private readonly MerchantService $merchantService,
    ) {}

    public function pay(MerchantPaymentDto $dto): MerchantPayment
    {
        $merchant = $this->merchantService->findOrFail($dto->merchantId);
        $this->merchantService->ensureActive($merchant);

        if ($dto->amount < self::MIN_AMOUNT) {
            throw new MerchantPaymentAboveMaximumException($dto->amount, self::MIN_AMOUNT);
        }

        if ($dto->amount > $merchant->max_txn_amount) {
            throw new MerchantPaymentAboveMaximumException($dto->amount, $merchant->max_txn_amount);
        }

        $mdrFee = $this->calculateMdr($dto->amount, $merchant);
        $netAmount = $dto->amount - $mdrFee;

        $payment = $this->paymentRepository->create([
            'merchant_id' => $merchant->id,
            'store_id' => $dto->storeId,
            'payer_user_id' => $dto->payerUserId,
            'qr_code' => $dto->qrCode,
            'qr_type' => 'static',
            'amount' => $dto->amount,
            'mdr_fee' => $mdrFee,
            'net_amount' => $netAmount,
            'currency' => 'SYP',
            'status' => MerchantPaymentStatus::PAID->value,
            'paid_at' => now(),
        ]);

        MerchantPaymentCompleted::dispatch(
            $payment->id,
            $merchant->id,
            $dto->payerUserId,
            $dto->amount,
            $mdrFee,
            $netAmount,
        );

        return $payment;
    }

    public function refund(string $paymentId, string $reason): MerchantPayment
    {
        $payment = $this->paymentRepository->findById($paymentId);
        if (!$payment) {
            throw new MerchantNotFoundException($paymentId);
        }

        if ($payment->paid_at && $payment->paid_at->diffInDays(now()) > self::REFUND_DAYS) {
            throw new MerchantRefundExpiredException;
        }

        return $this->paymentRepository->update($paymentId, [
            'status' => MerchantPaymentStatus::REFUNDED->value,
            'refund_reason' => $reason,
            'refunded_at' => now(),
        ]);
    }

    public function findByUser(string $userId, int $perPage = 15): iterable
    {
        return $this->paymentRepository->findByUser($userId, $perPage);
    }

    public function findByMerchant(string $merchantId, int $perPage = 15): iterable
    {
        return $this->paymentRepository->findByMerchant($merchantId, $perPage);
    }

    public function findById(string $id): ?MerchantPayment
    {
        return $this->paymentRepository->findById($id);
    }

    private function calculateMdr(int $amount, $merchant): int
    {
        $basisPoints = $merchant->is_micro_merchant ? 75 : (int) round((float) $merchant->mdr_percentage * 100);
        $fee = (int) floor(($amount * $basisPoints) / 10000);
        return max($merchant->mdr_min_syp, min($fee, $merchant->mdr_max_syp));
    }
}
