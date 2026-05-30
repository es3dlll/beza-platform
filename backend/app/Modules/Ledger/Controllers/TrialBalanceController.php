<?php
declare(strict_types=1);

namespace Modules\Ledger\Controllers;

use Modules\Ledger\Services\TrialBalanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class TrialBalanceController
{
    use ApiResponse;
    public function __construct(
        private readonly TrialBalanceService $trialBalance,
    ) {}

    public function index(): JsonResponse
    {
        return $this->respond($this->trialBalance->generate());
    }
}
