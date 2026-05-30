<?php

declare(strict_types=1);

namespace Modules\Financing\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Financing\Models\LoanProduct;
use Modules\Financing\Models\Loan;
use Modules\Financing\Models\LoanRepayment;
use Modules\Financing\Enums\LoanStatus;
use Modules\Financing\Exceptions\LoanProductNotFoundException;
use Modules\Financing\Exceptions\LoanNotApprovedException;
use Modules\Financing\Exceptions\RepaymentAmountExceedsBalanceException;

class FinancingService
{
    public function listProducts(): iterable
    {
        return LoanProduct::where('is_active', true)->get();
    }

    public function findProduct(string $id): LoanProduct
    {
        $p = LoanProduct::find($id);
        if (!$p) throw new LoanProductNotFoundException($id);
        return $p;
    }

    public function apply(string $userId, string $productId, int $amount, int $termDays, ?string $purpose = null): Loan
    {
        $product = $this->findProduct($productId);
        $interest = $amount * ($product->interest_rate / 100) * ($termDays / 365);
        $totalRepayable = $amount + (int) round($interest);
        $loan = Loan::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'loan_product_id' => $productId,
            'principal' => $amount,
            'total_repayable' => $totalRepayable,
            'outstanding_balance' => $totalRepayable,
            'interest_rate' => $product->interest_rate,
            'term_days' => $termDays,
            'status' => LoanStatus::PENDING->value,
            'purpose' => $purpose,
        ]);
        $this->generateInstallments($loan, $totalRepayable, $termDays);
        return $loan;
    }

    private function generateInstallments(Loan $loan, int $total, int $days): void
    {
        $count = max(1, (int) ceil($days / 30));
        $perInstallment = (int) floor($total / $count);
        $remainder = $total - ($perInstallment * $count);
        $now = now();
        for ($i = 1; $i <= $count; $i++) {
            LoanRepayment::create([
                'id' => (string) Str::ulid(),
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'amount' => $perInstallment + ($i === $count ? $remainder : 0),
                'paid_amount' => 0,
                'due_date' => $now->copy()->addDays(30 * $i),
                'status' => 'pending',
            ]);
        }
    }

    public function approve(string $loanId): Loan
    {
        $loan = Loan::findOrFail($loanId);
        $loan->update(['status' => LoanStatus::APPROVED->value, 'approved_at' => now()]);
        return $loan;
    }

    public function disburse(string $loanId): Loan
    {
        $loan = Loan::findOrFail($loanId);
        if ($loan->status !== LoanStatus::APPROVED->value) throw new LoanNotApprovedException;
        $loan->update(['status' => LoanStatus::DISBURSED->value, 'disbursed_at' => now()]);
        return $loan;
    }

    public function repay(string $loanId, int $amount): LoanRepayment
    {
        $loan = Loan::findOrFail($loanId);
        $nextInstallment = LoanRepayment::where('loan_id', $loanId)->where('status', 'pending')->orderBy('installment_number')->first();
        if (!$nextInstallment) throw new LoanAlreadyCompletedException;
        if ($amount > $nextInstallment->amount - $nextInstallment->paid_amount) throw new RepaymentAmountExceedsBalanceException;
        $paidAmount = $nextInstallment->paid_amount + $amount;
        $nextInstallment->update(['paid_amount' => $paidAmount, 'status' => $paidAmount >= $nextInstallment->amount ? 'paid' : 'partial', 'paid_at' => $paidAmount >= $nextInstallment->amount ? now() : null]);
        $loan->decrement('outstanding_balance', $amount);
        if ($loan->outstanding_balance <= 0) $loan->update(['status' => LoanStatus::COMPLETED->value, 'completed_at' => now()]);
        return $nextInstallment;
    }

    public function userLoans(string $userId): iterable
    {
        return Loan::where('user_id', $userId)->with('repayments')->orderByDesc('created_at')->get();
    }
}
