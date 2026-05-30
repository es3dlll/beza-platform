<?php

declare(strict_types=1);

namespace Modules\Education\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StudentRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $studentId,
        public readonly string $userId,
        public readonly string $institutionId,
    ) {}
}
