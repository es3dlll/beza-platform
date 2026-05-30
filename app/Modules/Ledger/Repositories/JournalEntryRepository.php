<?php
declare(strict_types=1);

namespace Modules\Ledger\Repositories;

use Modules\Ledger\Models\JournalEntry;
use Modules\Ledger\Models\JournalLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class JournalEntryRepository
{
    public function findById(string $id): ?JournalEntry
    {
        return JournalEntry::with('lines')->find($id);
    }

    public function findByReference(string $referenceType, string $referenceId): Collection
    {
        return JournalEntry::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->with('lines')
            ->get();
    }

    public function findByDateRange(\DateTimeInterface $from, \DateTimeInterface $to): Collection
    {
        return JournalEntry::whereBetween('posted_at', [$from, $to])
            ->with('lines')
            ->orderBy('posted_at')
            ->get();
    }

    public function save(JournalEntry $entry, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($entry, $lines) {
            $entry->save();
            foreach ($lines as $line) {
                $line->journal_entry_id = $entry->id;
                $line->save();
            }
            return $entry->fresh('lines');
        });
    }
}
