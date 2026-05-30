<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AidProgramCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $programId,
        public readonly string $organizationId,
        public readonly string $name,
        public readonly int $budget,
        public readonly string $currency,
    ) {}
}
