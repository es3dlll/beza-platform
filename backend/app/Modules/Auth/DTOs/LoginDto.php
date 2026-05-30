<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final class LoginDto
{
    public function __construct(
        public readonly string $phone,
        public readonly string $pin,
        public readonly ?string $deviceId = null,
        public readonly ?string $deviceName = null,
        public readonly ?string $deviceType = null,
        public readonly ?string $fcmToken = null,
    ) {}
}
