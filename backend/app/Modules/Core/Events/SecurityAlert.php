<?php

declare(strict_types=1);

namespace App\Modules\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class SecurityAlert
{
    use Dispatchable;

    public function __construct(
        public readonly string $feedbackId,
        public readonly string $userId,
        public readonly string $description,
        public readonly int $timestamp,
    ) {}
}
