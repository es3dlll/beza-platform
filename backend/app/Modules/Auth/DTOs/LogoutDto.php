<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

class LogoutDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $sessionId,
    ) {}
}
