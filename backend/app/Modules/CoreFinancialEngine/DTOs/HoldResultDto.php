<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\DTOs;

final class HoldResultDto
{
    public function __construct(
        public readonly bool $success,
        public readonly string $holdId,
        public readonly int $amount,
        public readonly string $status,
        public readonly ?string $error = null,
    ) {}
}
