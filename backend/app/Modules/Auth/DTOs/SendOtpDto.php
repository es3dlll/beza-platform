<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final class SendOtpDto
{
    public function __construct(
        public readonly string $phone,
        public readonly string $purpose,
    ) {}
}
