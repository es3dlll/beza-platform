<?php

declare(strict_types=1);

namespace Modules\Identity\DTOs;

class VerifyOtpDto
{
    public function __construct(
        public readonly string $phone,
        public readonly string $code,
        public readonly string $purpose,
    ) {}
}
