<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\DTOs;

final class PostingResultDto
{
    public function __construct(
        public readonly bool $success,
        public readonly string $transactionId,
        public readonly string $journalEntryId,
        public readonly int $totalAmount,
        public readonly string $currency,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
    ) {}
}
