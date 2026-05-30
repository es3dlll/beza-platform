<?php

declare(strict_types=1);

namespace Modules\Payroll\DTOs;

class RegisterEmployerDto
{
    public function __construct(
        public readonly string $userId = '',
        public readonly string $companyName = '',
        public readonly string $companyNameAr = '',
        public readonly string $phone = '',
        public readonly string $governorate = '',
        public readonly string $city = '',
        public readonly ?string $commercialRegistration = null,
        public readonly ?string $taxNumber = null,
        public readonly ?string $email = null,
        public readonly ?string $address = null,
    ) {}
}
