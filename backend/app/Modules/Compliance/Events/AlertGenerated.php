<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class AlertGenerated
{
    public function __construct(
        public string $alertId,
        public string $caseId,
        public string $severity,
        public string $message,
        public int $timestamp,
    ) {}
}
