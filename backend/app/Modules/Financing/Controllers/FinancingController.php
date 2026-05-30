<?php

declare(strict_types=1);

namespace Modules\Financing\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Financing\Services\FinancingService;
use Modules\Financing\Exceptions\LoanProductNotFoundException;
use Modules\Financing\Exceptions\LoanNotApprovedException;
use Modules\Financing\Exceptions\LoanAlreadyCompletedException;
use Modules\Financing\Exceptions\RepaymentAmountExceedsBalanceException;
use Modules\Financing\Exceptions\CreditScoreTooLowException;

class FinancingController extends Controller
{
    public function __construct(private readonly FinancingService $service) {}

    public function products(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->listProducts($request->query('type'))]);
    }

    public function showProduct(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->service->findProduct($id)]);
        } catch (LoanProductNotFoundException $e) {
            return response()->json(['error' => 'PRODUCT_NOT_FOUND'], 404);
        }
    }

    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string|size:26',
            'amount' => 'required|integer|min:1000',
            'term_days' => 'required|integer|min:7|max:365',
            'purpose' => 'nullable|string|max:255',
        ]);

        try {
            $loan = $this->service->apply(
                $request->user()->id,
                $request->input('product_id'),
                $request->integer('amount'),
                $request->integer('term_days'),
                $request->input('purpose'),
            );
            return response()->json(['data' => $loan], 201);
        } catch (CreditScoreTooLowException $e) {
            return response()->json(['error' => 'CREDIT_SCORE_TOO_LOW', 'reason' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => 'INVALID_AMOUNT', 'reason' => $e->getMessage()], 422);
        }
    }

    public function approve(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->approve($id)]);
    }

    public function disburse(string $id): JsonResponse
    {
        try {
            return response()->json(['data' => $this->service->disburse($id)]);
        } catch (LoanNotApprovedException $e) {
            return response()->json(['error' => 'LOAN_NOT_APPROVED'], 422);
        }
    }

    public function repay(Request $request, string $id): JsonResponse
    {
        $request->validate(['amount' => 'required|integer|min:1']);

        try {
            return response()->json(['data' => $this->service->repay($id, $request->integer('amount'))]);
        } catch (RepaymentAmountExceedsBalanceException $e) {
            return response()->json(['error' => 'REPAYMENT_EXCEEDS_BALANCE'], 422);
        } catch (LoanAlreadyCompletedException $e) {
            return response()->json(['error' => 'LOAN_ALREADY_COMPLETED'], 422);
        }
    }

    public function myLoans(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->service->userLoans($request->user()->id)]);
    }

    public function schedule(string $id): JsonResponse
    {
        return response()->json(['data' => $this->service->schedule($id)]);
    }

    public function bnplCheckout(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|string',
            'amount' => 'required|integer|min:1000',
            'merchant_tx_id' => 'required|string',
        ]);

        try {
            $loan = $this->service->bnplCheckout(
                $request->user()->id,
                $request->input('merchant_id'),
                $request->integer('amount'),
                $request->input('merchant_tx_id'),
            );
            return response()->json(['data' => $loan], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => 'BNPL_NOT_CONFIGURED'], 422);
        } catch (CreditScoreTooLowException $e) {
            return response()->json(['error' => 'CREDIT_SCORE_TOO_LOW'], 422);
        }
    }

    public function adminDashboard(): JsonResponse
    {
        return response()->json(['data' => $this->service->adminDashboard()]);
    }

    public function loansByStatus(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $perPage = (int) $request->query('per_page', 15);
        return response()->json(['data' => $this->service->loansByStatus($status, $perPage)]);
    }
}
