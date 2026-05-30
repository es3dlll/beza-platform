<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

class CreatePinDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $pin,
    ) {}
}
