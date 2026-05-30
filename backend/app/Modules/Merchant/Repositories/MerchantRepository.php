<?php

declare(strict_types=1);

namespace Modules\Merchant\Repositories;

use Modules\Merchant\Models\Merchant;
use Modules\Merchant\DTOs\RegisterMerchantDto;
use Modules\Merchant\Enums\MerchantStatus;

final class MerchantRepository
{
    public function create(RegisterMerchantDto $dto): Merchant
    {
        return Merchant::create([
            'user_id' => $dto->userId,
            'business_name' => $dto->businessName,
            'business_name_ar' => $dto->businessNameAr,
            'phone' => $dto->phone,
            'governorate' => $dto->governorate,
            'city' => $dto->city,
            'commercial_registration' => $dto->commercialRegistration,
            'tax_number' => $dto->taxNumber,
            'email' => $dto->email,
            'address' => $dto->address,
            'category' => $dto->category,
            'status' => MerchantStatus::PENDING->value,
        ]);
    }

    public function findById(string $id): ?Merchant
    {
        return Merchant::find($id);
    }

    public function findByUser(string $userId): ?Merchant
    {
        return Merchant::where('user_id', $userId)->first();
    }

    public function findByPhone(string $phone): ?Merchant
    {
        return Merchant::where('phone', $phone)->first();
    }

    public function update(string $id, array $data): ?Merchant
    {
        $merchant = $this->findById($id);
        if (!$merchant) return null;
        $merchant->update($data);
        return $merchant->fresh();
    }
}
