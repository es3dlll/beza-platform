<?php
declare(strict_types=1);

namespace Modules\Ledger\Controllers;

use Modules\Ledger\DTOs\CreateAccountDto;
use Modules\Ledger\Models\LedgerAccount;
use Modules\Ledger\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccountController
{
    public function __construct(
        private readonly AccountService $accounts,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => LedgerAccount::all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $dto = new CreateAccountDto(
            accountNumber: $request->input('account_number'),
            name: $request->input('name'),
            type: $request->input('type'),
            currency: $request->input('currency', 'SYP'),
            parentId: $request->input('parent_id'),
            metadata: $request->input('metadata', []),
        );

        $account = $this->accounts->create($dto);
        return response()->json(['data' => $account], 201);
    }

    public function show(string $id): JsonResponse
    {
        $account = LedgerAccount::find($id);
        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }
        return response()->json(['data' => $account]);
    }

    public function balance(string $id): JsonResponse
    {
        $money = $this->accounts->getBalance($id);
        return response()->json([
            'data' => [
                'amount' => $money->toInt(),
                'amount_formatted' => $money->toFloat(),
                'currency' => $money->getCurrency()->getCode(),
            ],
        ]);
    }

    public function available(string $id): JsonResponse
    {
        $money = $this->accounts->getAvailableBalance($id);
        return response()->json([
            'data' => [
                'amount' => $money->toInt(),
                'amount_formatted' => $money->toFloat(),
                'currency' => $money->getCurrency()->getCode(),
            ],
        ]);
    }
}
