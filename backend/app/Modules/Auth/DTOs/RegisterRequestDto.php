<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final class RegisterRequestDto
{
    public function __construct(
        public readonly string $phone,
        public readonly string $phoneCountryCode = '963',
        public readonly string $locale = 'ar',
    ) {}
}
