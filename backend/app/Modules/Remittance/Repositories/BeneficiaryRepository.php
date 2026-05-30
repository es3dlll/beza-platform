<?php

declare(strict_types=1);

namespace Modules\Remittance\Repositories;

use Modules\Remittance\Models\Beneficiary;
use Modules\Remittance\DTOs\RegisterBeneficiaryDto;

final class BeneficiaryRepository
{
    public function create(RegisterBeneficiaryDto $dto): Beneficiary
    {
        return Beneficiary::create([
            'user_id' => $dto->userId,
            'full_name_ar' => $dto->fullNameAr,
            'full_name_en' => $dto->fullNameEn,
            'phone' => $dto->phone,
            'national_id' => $dto->nationalId,
            'relationship' => $dto->relationship,
            'governorate' => $dto->governorate,
            'city' => $dto->city,
            'address' => $dto->address,
            'kyc_completed' => false,
            'metadata' => $dto->metadata,
        ]);
    }

    public function findById(string $id): ?Beneficiary
    {
        return Beneficiary::find($id);
    }

    public function findByUser(string $userId): iterable
    {
        return Beneficiary::where('user_id', $userId)->get();
    }

    public function findByPhone(string $phone): ?Beneficiary
    {
        return Beneficiary::where('phone', $phone)->first();
    }

    public function completeKyc(string $id): ?Beneficiary
    {
        $beneficiary = $this->findById($id);
        if (!$beneficiary) {
            return null;
        }
        $beneficiary->update(['kyc_completed' => true]);
        return $beneficiary->fresh();
    }

    public function update(string $id, array $data): ?Beneficiary
    {
        $beneficiary = $this->findById($id);
        if (!$beneficiary) {
            return null;
        }
        $beneficiary->update($data);
        return $beneficiary->fresh();
    }

    public function delete(string $id): bool
    {
        return Beneficiary::destroy($id) > 0;
    }
}
