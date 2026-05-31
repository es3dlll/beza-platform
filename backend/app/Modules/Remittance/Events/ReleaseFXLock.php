<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Events;

final readonly class ReleaseFXLock
{
    public function __construct(
        public string $remittanceId,
        public string $reason,
    ) {}
}
