<?php
declare(strict_types=1);

namespace Modules\Ledger\Controllers;

use Modules\Ledger\Services\TrialBalanceService;
use Illuminate\Http\JsonResponse;

final class TrialBalanceController
{
    public function __construct(
        private readonly TrialBalanceService $trialBalance,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->trialBalance->generate()]);
    }
}
