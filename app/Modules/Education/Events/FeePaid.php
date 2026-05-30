<?php

declare(strict_types=1);

namespace Modules\Education\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FeePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $studentId,
        public readonly string $feeId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $reference,
    ) {}
}
