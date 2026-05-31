<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class SanctionHit
{
    public function __construct(
        public string $name,
        public string $matchType,
        public string $source,
        public int $timestamp,
    ) {}
}
