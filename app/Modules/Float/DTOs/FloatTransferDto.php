<?php

declare(strict_types=1);

namespace Modules\Float\DTOs;

final class FloatTransferDto
{
    public function __construct(
        public readonly string $fromFloatAccountId,
        public readonly string $toFloatAccountId,
        public readonly int $amount,
        public readonly string $description = '',
        public readonly ?string $referenceId = null,
    ) {}
}
