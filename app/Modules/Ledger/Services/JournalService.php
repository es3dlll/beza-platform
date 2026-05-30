<?php
declare(strict_types=1);

namespace Modules\Ledger\Services;

use Modules\Ledger\DTOs\PostEntryDto;
use Modules\Ledger\Events\JournalEntryPosted;
use Modules\Ledger\Exceptions\DoubleEntryViolationException;
use Modules\Ledger\Models\JournalEntry;
use Modules\Ledger\Models\JournalLine;
use Modules\Ledger\Repositories\JournalEntryRepository;
use Illuminate\Support\Str;

final class JournalService
{
    public function __construct(
        private readonly JournalEntryRepository $entries,
        private readonly AccountService $accounts,
    ) {}

    public function post(PostEntryDto $dto): JournalEntry
    {
        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($dto->lines as $line) {
            if ($line->type === 'debit') {
                $debitTotal += $line->amount;
            } else {
                $creditTotal += $line->amount;
            }
        }

        if ($debitTotal !== $creditTotal) {
            throw new DoubleEntryViolationException($debitTotal, $creditTotal);
        }

        $entry = new JournalEntry();
        $entry->id = Str::ulid()->toBase32();
        $entry->reference_type = $dto->referenceType;
        $entry->reference_id = $dto->referenceId;
        $entry->description = $dto->description;
        $entry->total_amount = $debitTotal;
        $entry->posted_at = $dto->postedAt ?? now();
        $entry->metadata = [];

        $lines = [];
        foreach ($dto->lines as $lineDto) {
            $line = new JournalLine();
            $line->id = Str::ulid()->toBase32();
            $line->account_id = $lineDto->accountId;
            $line->amount = $lineDto->amount;
            $line->type = $lineDto->type;
            $line->description = $lineDto->description;
            $lines[] = $line;

            $this->accounts->adjustBalance(
                $lineDto->accountId,
                $lineDto->amount,
                $lineDto->type,
                $entry->id,
            );
        }

        $entry = $this->entries->save($entry, $lines);

        event(new JournalEntryPosted(
            journalEntryId: $entry->id,
            referenceType: $entry->reference_type,
            referenceId: $entry->reference_id,
            totalAmount: $entry->total_amount,
            currency: 'SYP',
            postedAt: $entry->posted_at,
        ));

        return $entry;
    }
}
