<?php

declare(strict_types=1);

namespace Modules\Remittance\DTOs;

class RegisterBeneficiaryDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly string $fullNameAr = '',
        public readonly ?string $fullNameEn = null,
        public readonly string $phone = '',
        public readonly ?string $nationalId = null,
        public readonly string $relationship = '',
        public readonly ?string $governorate = null,
        public readonly ?string $city = null,
        public readonly ?string $address = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? '',
            fullNameAr: $data['full_name_ar'] ?? '',
            fullNameEn: $data['full_name_en'] ?? null,
            phone: $data['phone'] ?? '',
            nationalId: $data['national_id'] ?? null,
            relationship: $data['relationship'] ?? '',
            governorate: $data['governorate'] ?? null,
            city: $data['city'] ?? null,
            address: $data['address'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
