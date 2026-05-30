<?php

declare(strict_types=1);

namespace Modules\Fraud\DTOs;

final class FraudCheckDto
{
    public function __construct(
        public readonly string $eventType = '',
        public readonly ?string $actorId = null,
        public readonly ?string $actorType = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $deviceId = null,
        public readonly ?string $userAgent = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?int $amount = null,
        public readonly ?string $iban = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $fullName = null,
        public readonly array $metadata = [],
    ) {}
}
