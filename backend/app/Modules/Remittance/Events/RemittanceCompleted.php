<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Events;

final readonly class RemittanceCompleted
{
    public function __construct(
        public string $remittanceId,
        public string $status,
        public int $completedAt,
    ) {}
}
