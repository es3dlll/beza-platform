<?php

declare(strict_types=1);

namespace Modules\Settlement\DTOs;

final class CreateSettlementDto
{
    public function __construct(
        public readonly string $referenceType,
        public readonly string $referenceId,
        public readonly string $settlementType,
        public readonly int $grossAmount,
        public readonly int $feeAmount = 0,
        public readonly int $commissionAmount = 0,
        public readonly string $currency = 'SYP',
        public readonly ?string $settlementAccountId = null,
        public readonly ?\DateTimeInterface $periodStart = null,
        public readonly ?\DateTimeInterface $periodEnd = null,
        public readonly ?array $metadata = [],
    ) {}
}
