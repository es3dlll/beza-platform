<?php
declare(strict_types=1);

namespace Modules\Ledger\Controllers;

use Modules\Ledger\DTOs\JournalLineDto;
use Modules\Ledger\DTOs\PostEntryDto;
use Modules\Ledger\Exceptions\DoubleEntryViolationException;
use Modules\Ledger\Repositories\JournalEntryRepository;
use Modules\Ledger\Services\JournalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class JournalController
{
    use ApiResponse;
    public function __construct(
        private readonly JournalService $journal,
        private readonly JournalEntryRepository $entries,
    ) {}

    public function post(Request $request): JsonResponse
    {
        $lines = [];
        foreach ($request->input('lines', []) as $line) {
            $lines[] = new JournalLineDto(
                accountId: $line['account_id'],
                amount: $line['amount'],
                type: $line['type'],
                description: $line['description'] ?? null,
            );
        }

        $dto = new PostEntryDto(
            referenceType: $request->input('reference_type'),
            referenceId: $request->input('reference_id'),
            description: $request->input('description'),
            lines: $lines,
            postedAt: $request->input('posted_at') ? new \DateTime($request->input('posted_at')) : null,
        );

        try {
            $entry = $this->journal->post($dto);
        } catch (DoubleEntryViolationException $e) {
            return $this->respondError('DOUBLE_ENTRY_VIOLATION', $e->getMessage());
        }
        return $this->respondCreated($entry->load('lines'));
    }

    public function show(string $id): JsonResponse
    {
        $entry = $this->entries->findById($id);
        if (!$entry) {
            return $this->respondNotFound('JournalEntry');
        }
        return $this->respond($entry);
    }

    public function byReference(string $type, string $id): JsonResponse
    {
        $entries = $this->entries->findByReference($type, $id);
        return $this->respond($entries);
    }
}
