<?php

declare(strict_types=1);

namespace Modules\Financing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LoanApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $loanId,
        public readonly string $userId,
        public readonly int $amount,
        public readonly string $currency,
    ) {}
}
