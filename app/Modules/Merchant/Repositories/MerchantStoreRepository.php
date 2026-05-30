<?php

declare(strict_types=1);

namespace Modules\Merchant\Repositories;

use Modules\Merchant\Models\MerchantStore;
use Modules\Merchant\DTOs\CreateStoreDto;

class MerchantStoreRepository
{
    public function create(CreateStoreDto $dto): MerchantStore
    {
        return MerchantStore::create([
            'merchant_id' => $dto->merchantId,
            'name' => $dto->name,
            'name_ar' => $dto->nameAr,
            'phone' => $dto->phone,
            'governorate' => $dto->governorate,
            'city' => $dto->city,
            'address' => $dto->address,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
        ]);
    }

    public function findByMerchant(string $merchantId): iterable
    {
        return MerchantStore::where('merchant_id', $merchantId)
            ->where('is_active', true)->get();
    }

    public function findById(string $id): ?MerchantStore
    {
        return MerchantStore::find($id);
    }
}
