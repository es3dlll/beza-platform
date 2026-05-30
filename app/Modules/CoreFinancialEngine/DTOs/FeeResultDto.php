<?php

namespace Modules\CoreFinancialEngine\DTOs;

final class FeeResultDto
{
    public function __construct(
        public readonly bool $applied,
        public readonly int $feeAmount,
        public readonly string $currency,
        public readonly string $feeAccountId,
        public readonly ?string $journalEntryId = null,
        public readonly ?string $feeRule = null,
        public readonly ?string $error = null,
    ) {}
}
