<?php

namespace Modules\CoreFinancialEngine\Services;

use Modules\CoreFinancialEngine\DTOs\PostingInstructionDto;
use Modules\CoreFinancialEngine\DTOs\PostingResultDto;
use Modules\CoreFinancialEngine\Events\SettlementCompleted;
use App\Modules\Ledger\Repositories\LedgerAccountRepository;
use App\Modules\Ledger\Repositories\JournalEntryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class SettlementEngine
{
    public function __construct(
        private readonly PostingEngine $posting,
        private readonly LedgerAccountRepository $accounts,
        private readonly JournalEntryRepository $entries,
    ) {}

    public function settleBatch(array $transactions, string $settlementAccountId): PostingResultDto
    {
        $netAmount = 0;
        $lines = [];

        foreach ($transactions as $txn) {
            $netAmount += $txn['amount'];
            $lines[] = [
                'account_id' => $txn['account_id'],
                'amount' => $txn['amount'],
                'type' => $txn['direction'],
                'description' => $txn['description'] ?? 'Settlement',
            ];
        }

        if ($netAmount !== 0) {
            $lines[] = [
                'account_id' => $settlementAccountId,
                'amount' => abs($netAmount),
                'type' => $netAmount > 0 ? 'credit' : 'debit',
                'description' => 'Net settlement balancing entry',
            ];
        }

        $instruction = new PostingInstructionDto(
            referenceType: 'settlement',
            referenceId: Str::ulid()->toBase32(),
            description: 'Batch settlement of ' . count($transactions) . ' transactions',
            lines: $lines,
            channel: 'system',
            metadata: ['batch_size' => count($transactions)],
        );

        $result = $this->posting->execute($instruction);

        if ($result->success) {
            event(new SettlementCompleted(
                batchId: $result->transactionId,
                transactionCount: count($transactions),
                netAmount: $netAmount,
                settlementAccountId: $settlementAccountId,
            ));
        }

        return $result;
    }

    public function dailyCutoff(string $date): array
    {
        $from = "{$date} 00:00:00";
        $to = "{$date} 23:59:59";

        $entries = $this->entries->findByDateRange(
            new \DateTime($from),
            new \DateTime($to)
        );

        $summary = [
            'date' => $date,
            'total_entries' => $entries->count(),
            'total_amount' => $entries->sum('total_amount'),
            'by_type' => $entries->groupBy('reference_type')->map->count(),
        ];

        return $summary;
    }
}
