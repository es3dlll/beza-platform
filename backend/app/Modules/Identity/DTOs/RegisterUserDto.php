<?php

declare(strict_types=1);

namespace Modules\Identity\DTOs;

final class RegisterUserDto
{
    public function __construct(
        public readonly string $phone,
        public readonly string $phoneCountryCode = '963',
        public readonly string $locale = 'ar',
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
    ) {}
}
