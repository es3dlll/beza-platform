<?php

declare(strict_types=1);

namespace Modules\Identity\DTOs;

class CreatePinDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $pin,
    ) {}
}
