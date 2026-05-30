<?php
declare(strict_types=1);

namespace Modules\Ledger\Controllers;

use Modules\Ledger\DTOs\CreateAccountDto;
use Modules\Ledger\Models\LedgerAccount;
use Modules\Ledger\Services\AccountService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccountController
{
    use ApiResponse;
    public function __construct(
        private readonly AccountService $accounts,
    ) {}

    public function index(): JsonResponse
    {
        return $this->respond(LedgerAccount::all());
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
        return $this->respondCreated($account);
    }

    public function show(string $id): JsonResponse
    {
        $account = LedgerAccount::find($id);
        if (!$account) {
            return $this->respondNotFound('Account');
        }
        return $this->respond($account);
    }

    public function balance(string $id): JsonResponse
    {
        $money = $this->accounts->getBalance($id);
        return $this->respond([
            'amount' => $money->toInt(),
            'amount_formatted' => $money->toFloat(),
            'currency' => $money->getCurrency()->getCode(),
        ]);
    }

    public function available(string $id): JsonResponse
    {
        $money = $this->accounts->getAvailableBalance($id);
        return $this->respond([
            'amount' => $money->toInt(),
            'amount_formatted' => $money->toFloat(),
            'currency' => $money->getCurrency()->getCode(),
        ]);
    }
}
