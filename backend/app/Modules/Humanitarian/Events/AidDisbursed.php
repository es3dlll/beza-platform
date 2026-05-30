<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AidDisbursed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $disbursementId,
        public readonly string $programId,
        public readonly string $beneficiaryId,
        public readonly int $amount,
        public readonly string $currency,
    ) {}
}
