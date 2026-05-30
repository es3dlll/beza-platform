<?php
declare(strict_types=1);

namespace Modules\Ledger\Controllers;

use Modules\Ledger\DTOs\JournalLineDto;
use Modules\Ledger\DTOs\PostEntryDto;
use Modules\Ledger\Repositories\JournalEntryRepository;
use Modules\Ledger\Services\JournalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class JournalController
{
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

        $entry = $this->journal->post($dto);
        return response()->json(['data' => $entry->load('lines')], 201);
    }

    public function show(string $id): JsonResponse
    {
        $entry = $this->entries->findById($id);
        if (!$entry) {
            return response()->json(['error' => 'Journal entry not found'], 404);
        }
        return response()->json(['data' => $entry]);
    }

    public function byReference(string $type, string $id): JsonResponse
    {
        $entries = $this->entries->findByReference($type, $id);
        return response()->json(['data' => $entries]);
    }
}
