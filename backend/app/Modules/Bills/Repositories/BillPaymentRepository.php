<?php

declare(strict_types=1);

namespace Modules\Bills\Repositories;

use Modules\Bills\Models\BillPayment;
use Modules\Bills\Enums\BillPaymentStatus;

final class BillPaymentRepository
{
    public function create(array $data): BillPayment
    {
        return BillPayment::create($data);
    }

    public function findById(string $id): ?BillPayment
    {
        return BillPayment::with('provider')->find($id);
    }

    public function findByUser(string $userId, int $perPage = 15): iterable
    {
        return BillPayment::where('user_id', $userId)
            ->with('provider')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function update(string $id, array $data): ?BillPayment
    {
        $payment = $this->findById($id);
        if (!$payment) {
            return null;
        }
        $payment->update($data);
        return $payment->fresh();
    }

    public function countRecentRetries(string $userId, string $providerId, int $minutes = 15): int
    {
        return BillPayment::where('user_id', $userId)
            ->where('bill_provider_id', $providerId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->whereIn('status', [BillPaymentStatus::FAILED->value])
            ->count();
    }
}
