<?php

declare(strict_types=1);

namespace Modules\Merchant\Repositories;

use Modules\Merchant\Models\MerchantPayment;

class MerchantPaymentRepository
{
    public function create(array $data): MerchantPayment
    {
        return MerchantPayment::create($data);
    }

    public function findById(string $id): ?MerchantPayment
    {
        return MerchantPayment::with(['merchant', 'store'])->find($id);
    }

    public function findByUser(string $userId, int $perPage = 15): iterable
    {
        return MerchantPayment::where('payer_user_id', $userId)
            ->with('merchant')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByMerchant(string $merchantId, int $perPage = 15): iterable
    {
        return MerchantPayment::where('merchant_id', $merchantId)
            ->with('payer')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByQrCode(string $qrCode): ?MerchantPayment
    {
        return MerchantPayment::where('qr_code', $qrCode)
            ->with('merchant')
            ->first();
    }

    public function update(string $id, array $data): ?MerchantPayment
    {
        $payment = $this->findById($id);
        if (!$payment) return null;
        $payment->update($data);
        return $payment->fresh();
    }
}
