<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\DTOs;

final class ReversalInstructionDto
{
    public function __construct(
        public readonly string $originalTransactionId,
        public readonly string $reason,
        public readonly string $initiatedBy,
        public readonly ?array $metadata = [],
    ) {}
}
