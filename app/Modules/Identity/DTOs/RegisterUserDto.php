<?php

declare(strict_types=1);

namespace Modules\Identity\DTOs;

class RegisterUserDto
{
    public function __construct(
        public readonly string $phone,
        public readonly string $phoneCountryCode = '963',
        public readonly string $locale = 'ar',
    ) {}
}
