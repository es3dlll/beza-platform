<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Services;

use App\Modules\Ledger\Events\JournalEntryPosted;
use App\Modules\Ledger\Exceptions\ImbalancedJournalException;
use App\Modules\Ledger\Exceptions\JournalNotFoundException;
use App\Modules\Ledger\Models\JournalEntry;
use App\Modules\Ledger\Models\JournalLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class JournalService
{
    public function __construct(
        private readonly HashChainService $hashChain,
        private readonly AccountService $accounts,
    ) {}

    public function postEntry(
        string $transactionId,
        array $debits,
        array $credits,
        ?string $description = null,
        ?string $descriptionAr = null,
    ): JournalEntry {
        $totalDebits = collect($debits)->sum(fn ($d) => $d['amount']);
        $totalCredits = collect($credits)->sum(fn ($c) => $c['amount']);

        if ($totalDebits !== $totalCredits) {
            throw new ImbalancedJournalException($totalDebits, $totalCredits);
        }

        return DB::transaction(function () use ($transactionId, $debits, $credits, $description, $descriptionAr, $totalDebits, $totalCredits) {
            $latestEntry = JournalEntry::orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $previousHash = $latestEntry?->hash;
            $salt = Str::random(16);

            $entryId = Str::ulid()->toBase32();

            $entry = JournalEntry::create([
                'id' => $entryId,
                'transaction_id' => $transactionId,
                'description' => $description,
                'description_ar' => $descriptionAr,
                'previous_hash' => $previousHash,
                'hash' => $entryId,
                'metadata' => ['salt' => $salt],
            ]);

            $entry->hash = $this->hashChain->calculateHash($entry);
            $entry->save();

            $lines = [];

            foreach ($debits as $line) {
                $journalLine = JournalLine::create([
                    'id' => Str::ulid()->toBase32(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'type' => 'debit',
                    'amount' => $line['amount'],
                    'currency' => $line['currency'] ?? 'SYP',
                    'description' => $line['description'] ?? null,
                ]);

                $this->accounts->updateBalance($line['account_id'], $line['amount'], 'debit', $transactionId);
                $lines[] = $journalLine;
            }

            foreach ($credits as $line) {
                $journalLine = JournalLine::create([
                    'id' => Str::ulid()->toBase32(),
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'type' => 'credit',
                    'amount' => $line['amount'],
                    'currency' => $line['currency'] ?? 'SYP',
                    'description' => $line['description'] ?? null,
                ]);

                $this->accounts->updateBalance($line['account_id'], $line['amount'], 'credit', $transactionId);
                $lines[] = $journalLine;
            }

            $entry->load('lines');

            event(new JournalEntryPosted(
                entryId: $entry->id,
                transactionId: $transactionId,
                lines: $entry->lines->toArray(),
                totalDebit: $totalDebits,
                totalCredit: $totalCredits,
                hash: $entry->hash,
            ));

            return $entry;
        });
    }

    public function getEntry(string $id): JournalEntry
    {
        return JournalEntry::with('lines.account')
            ->find($id) ?? throw new JournalNotFoundException($id);
    }

    public function getTrialBalance(?string $currency = null): Collection
    {
        $query = \App\Modules\Ledger\Models\LedgerAccount::orderBy('code');

        if ($currency !== null) {
            $query->where('currency', $currency);
        }

        return $query->get()->map(fn ($account) => [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'name_ar' => $account->name_ar,
            'type' => $account->type,
            'balance' => $account->balance,
            'currency' => $account->currency,
            'normal_balance' => in_array($account->type, ['asset', 'expense']) ? 'debit' : 'credit',
        ]);
    }

    public function getAccountBalance(string $accountId): int
    {
        return $this->accounts->getAccount($accountId)->balance;
    }

    public function verifyChain(): array
    {
        return $this->hashChain->verifyIntegrity();
    }
}
