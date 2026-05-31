<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

final class HashChainService
{
    public function calculateHash(JournalEntry $entry): string
    {
        $data = implode('|', [
            $entry->previous_hash ?? '',
            $entry->id,
            $entry->transaction_id ?? '',
            $entry->description ?? '',
            $entry->created_at?->toIso8601String() ?? now()->toIso8601String(),
            $entry->metadata['salt'] ?? '',
        ]);

        return hash('sha256', $data);
    }

    public function validateChain(?JournalEntry $previous, JournalEntry $current): bool
    {
        if ($current->previous_hash === null) {
            return $previous === null;
        }

        if ($previous === null) {
            return false;
        }

        return $current->previous_hash === $previous->hash
            && $current->hash === $this->calculateHash($current)
            && $previous->hash === $this->calculateHash($previous);
    }

    public function verifyIntegrity(): array
    {
        $entries = JournalEntry::orderBy('created_at')->orderBy('id')->get();

        $total = $entries->count();
        $verified = 0;
        $failed = 0;

        if ($total === 0) {
            return ['passed' => true, 'total' => 0, 'verified' => 0, 'failed' => 0];
        }

        $previous = null;

        foreach ($entries as $entry) {
            $computedHash = $this->calculateHash($entry);

            if ($entry->hash !== $computedHash) {
                $failed++;
                $previous = $entry;
                continue;
            }

            if ($entry->previous_hash !== null) {
                if ($previous === null || $entry->previous_hash !== $previous->hash) {
                    $failed++;
                    $previous = $entry;
                    continue;
                }
            }

            $verified++;
            $previous = $entry;
        }

        return [
            'passed' => $failed === 0,
            'total' => $total,
            'verified' => $verified,
            'failed' => $failed,
        ];
    }
}
