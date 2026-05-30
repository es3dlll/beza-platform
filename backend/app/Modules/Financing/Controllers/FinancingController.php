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
use App\Support\ApiResponse;

final class FinancingController extends Controller
{
    use ApiResponse;
    public function __construct(private readonly FinancingService $service) {}

    public function products(Request $request): JsonResponse
    {
        return $this->respond($this->service->listProducts($request->query('type')));
    }

    public function showProduct(string $id): JsonResponse
    {
        try {
            return $this->respond($this->service->findProduct($id));
        } catch (LoanProductNotFoundException $e) {
            return $this->respondError('PRODUCT_NOT_FOUND', 'Product not found', 'المنتج غير موجود', 404);
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
            return $this->respondCreated($loan);
        } catch (CreditScoreTooLowException $e) {
            return $this->respondError('CREDIT_SCORE_TOO_LOW', $e->getMessage(), null, 422);
        } catch (\InvalidArgumentException $e) {
            return $this->respondError('INVALID_AMOUNT', $e->getMessage(), null, 422);
        }
    }

    public function approve(string $id): JsonResponse
    {
        return $this->respond($this->service->approve($id));
    }

    public function disburse(string $id): JsonResponse
    {
        try {
            return $this->respond($this->service->disburse($id));
        } catch (LoanNotApprovedException $e) {
            return $this->respondError('LOAN_NOT_APPROVED', 'Loan not approved', 'القرض غير معتمد', 422);
        }
    }

    public function repay(Request $request, string $id): JsonResponse
    {
        $request->validate(['amount' => 'required|integer|min:1']);

        try {
            return $this->respond($this->service->repay($id, $request->integer('amount')));
        } catch (RepaymentAmountExceedsBalanceException $e) {
            return $this->respondError('REPAYMENT_EXCEEDS_BALANCE', 'Repayment exceeds balance', 'السداد يتجاوز الرصيد', 422);
        } catch (LoanAlreadyCompletedException $e) {
            return $this->respondError('LOAN_ALREADY_COMPLETED', 'Loan already completed', 'القرض مكتمل بالفعل', 422);
        }
    }

    public function myLoans(Request $request): JsonResponse
    {
        return $this->respond($this->service->userLoans($request->user()->id));
    }

    public function schedule(string $id): JsonResponse
    {
        return $this->respond($this->service->schedule($id));
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
            return $this->respondCreated($loan);
        } catch (\RuntimeException $e) {
            return $this->respondError('BNPL_NOT_CONFIGURED', 'BNPL not configured', 'غير مهيأ', 422);
        } catch (CreditScoreTooLowException $e) {
            return $this->respondError('CREDIT_SCORE_TOO_LOW', 'Credit score too low', 'درجة الائتمان منخفضة جداً', 422);
        }
    }

    public function adminDashboard(): JsonResponse
    {
        return $this->respond($this->service->adminDashboard());
    }

    public function loansByStatus(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');
        $perPage = (int) $request->query('per_page', 15);
        return $this->respond($this->service->loansByStatus($status, $perPage));
    }
}
