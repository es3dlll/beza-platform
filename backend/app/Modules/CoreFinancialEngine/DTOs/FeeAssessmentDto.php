<?php

namespace Modules\CoreFinancialEngine\DTOs;

final class FeeAssessmentDto
{
    public function __construct(
        public readonly string $feeType,
        public readonly string $accountId,
        public readonly int $transactionAmount,
        public readonly string $currency,
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId = null,
        public readonly ?array $metadata = [],
    ) {}
}
