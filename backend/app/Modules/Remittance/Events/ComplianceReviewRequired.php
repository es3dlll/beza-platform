<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Events;

final readonly class ComplianceReviewRequired
{
    public function __construct(
        public string $remittanceId,
        public string $senderId,
        public int $amount,
        public string $reason,
    ) {}
}
