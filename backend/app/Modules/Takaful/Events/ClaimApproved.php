<?php

declare(strict_types=1);

namespace Modules\Takaful\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ClaimApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $claimId,
        public readonly int $payoutAmount,
    ) {}
}
