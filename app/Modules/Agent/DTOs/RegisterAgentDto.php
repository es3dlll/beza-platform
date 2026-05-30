<?php

declare(strict_types=1);

namespace Modules\Agent\DTOs;

final class RegisterAgentDto
{
    public function __construct(
        public readonly string $userId,
        public readonly string $businessName,
        public readonly string $governorate,
        public readonly string $city,
        public readonly string $phone,
        public readonly string $agentType = 'retail',
        public readonly ?string $area = null,
        public readonly ?string $address = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $altPhone = null,
        public readonly ?array $metadata = [],
    ) {}
}
