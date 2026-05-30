<?php

declare(strict_types=1);

namespace Modules\Float\DTOs;

final class FloatTransactionDto
{
    public function __construct(
        public readonly string $floatAccountId,
        public readonly string $type,
        public readonly int $amount,
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId = null,
        public readonly ?string $description = null,
    ) {}
}
