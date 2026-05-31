<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Events;

final readonly class ListUpdatedEvent
{
    public function __construct(
        public string $source,
        public int $recordsCount,
        public int $timestamp,
    ) {}
}
