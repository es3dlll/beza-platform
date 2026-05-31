<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class CaseEscalated
{
    public function __construct(
        public string $caseId,
        public string $previousStatus,
        public string $reason,
        public int $timestamp,
    ) {}
}
