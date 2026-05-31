<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class CaseResolved
{
    public function __construct(
        public string $caseId,
        public string $resolution,
        public string $reviewerId,
        public int $timestamp,
    ) {}
}
