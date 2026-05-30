<?php
declare(strict_types=1);

namespace Modules\Ledger\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class JournalEntryPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $journalEntryId,
        public readonly string $referenceType,
        public readonly string $referenceId,
        public readonly int $totalAmount,
        public readonly string $currency,
        public readonly \DateTimeInterface $postedAt,
    ) {}
}
