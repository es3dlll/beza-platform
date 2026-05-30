<?php

declare(strict_types=1);

namespace Modules\Merchant\DTOs;

final class RegisterMerchantDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly string $businessName = '',
        public readonly string $businessNameAr = '',
        public readonly string $phone = '',
        public readonly string $governorate = '',
        public readonly string $city = '',
        public readonly ?string $commercialRegistration = null,
        public readonly ?string $taxNumber = null,
        public readonly ?string $email = null,
        public readonly ?string $address = null,
        public readonly ?string $category = null,
    ) {}
}
