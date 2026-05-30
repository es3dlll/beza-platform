<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final class VerifyOtpDto
{
    public function __construct(
        public readonly string $phone,
        public readonly string $code,
        public readonly string $purpose = 'register',
    ) {}
}
