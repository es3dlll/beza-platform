<?php

declare(strict_types=1);

namespace Modules\Takaful\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PolicySubscribed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $policyId,
        public readonly string $userId,
        public readonly string $productId,
        public readonly int $premium,
    ) {}
}
