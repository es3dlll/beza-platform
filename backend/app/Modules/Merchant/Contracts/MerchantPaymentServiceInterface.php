<?php

declare(strict_types=1);

namespace Modules\Merchant\Contracts;

use Modules\Merchant\DTOs\MerchantPaymentDto;
use Modules\Merchant\Models\MerchantPayment;

interface MerchantPaymentServiceInterface
{
    public function pay(MerchantPaymentDto $dto): MerchantPayment;

    public function refund(string $paymentId, string $reason): MerchantPayment;

    public function findByUser(string $userId, int $perPage = 15): iterable;

    public function findByMerchant(string $merchantId, int $perPage = 15): iterable;

    public function findById(string $id): ?MerchantPayment;
}
