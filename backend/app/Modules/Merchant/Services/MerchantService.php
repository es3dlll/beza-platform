<?php

declare(strict_types=1);

namespace Modules\Merchant\Services;

use Illuminate\Support\Str;
use Modules\Merchant\Models\Merchant;
use Modules\Merchant\DTOs\RegisterMerchantDto;
use Modules\Merchant\DTOs\CreateStoreDto;
use Modules\Merchant\Repositories\MerchantRepository;
use Modules\Merchant\Repositories\MerchantStoreRepository;
use Modules\Merchant\Enums\MerchantStatus;
use Modules\Merchant\Events\MerchantRegistered;
use Modules\Merchant\Events\MerchantApproved;
use Modules\Merchant\Exceptions\MerchantNotFoundException;
use Modules\Merchant\Exceptions\MerchantSuspendedException;

final class MerchantService
{
    public function __construct(
        private readonly MerchantRepository $merchantRepository,
        private readonly MerchantStoreRepository $storeRepository,
    ) {}

    public function register(RegisterMerchantDto $dto): Merchant
    {
        $merchant = $this->merchantRepository->create($dto);

        MerchantRegistered::dispatch($merchant->id, $dto->userId, $dto->businessName);

        return $merchant;
    }

    public function approve(string $merchantId, string $approvedBy): Merchant
    {
        $merchant = $this->findOrFail($merchantId);

        $merchant = $this->merchantRepository->update($merchantId, [
            'status' => MerchantStatus::ACTIVE->value,
            'approved_at' => now(),
        ]);

        MerchantApproved::dispatch($merchantId, $approvedBy);

        return $merchant;
    }

    public function suspend(string $merchantId): Merchant
    {
        return $this->merchantRepository->update($merchantId, [
            'status' => MerchantStatus::SUSPENDED->value,
        ]);
    }

    public function findOrFail(string $id): Merchant
    {
        $merchant = $this->merchantRepository->findById($id);
        if (!$merchant) {
            throw new MerchantNotFoundException($id);
        }
        return $merchant;
    }

    public function findByUser(string $userId): ?Merchant
    {
        return $this->merchantRepository->findByUser($userId);
    }

    public function ensureActive(Merchant $merchant): void
    {
        if (!$merchant->status || $merchant->status !== MerchantStatus::ACTIVE->value) {
            throw new MerchantSuspendedException($merchant->id);
        }
    }

    public function createStore(CreateStoreDto $dto): \Modules\Merchant\Models\MerchantStore
    {
        return $this->storeRepository->create($dto);
    }

    public function getStores(string $merchantId): iterable
    {
        return $this->storeRepository->findByMerchant($merchantId);
    }

    public function generateQrCode(): string
    {
        return 'MQR-' . strtoupper(Str::random(20));
    }
}
