<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final class CreatePinDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $pin,
    ) {}
}
