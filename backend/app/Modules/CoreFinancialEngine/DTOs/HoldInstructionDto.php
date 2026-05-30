<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\DTOs;

final class HoldInstructionDto
{
    public function __construct(
        public readonly string $accountId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $reason,
        public readonly string $referenceType,
        public readonly string $referenceId,
        public readonly ?\DateTimeInterface $expiresAt = null,
    ) {}
}
