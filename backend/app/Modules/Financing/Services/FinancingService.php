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
use Modules\Financing\Exceptions\LoanAlreadyCompletedException;
use Modules\Financing\Exceptions\RepaymentAmountExceedsBalanceException;
use Modules\Financing\Exceptions\LoanUnderReviewException;
use Modules\Financing\Exceptions\CreditScoreTooLowException;
use Modules\Identity\Models\User;

final class FinancingService
{
    private const MIN_SCORE_THRESHOLD = 300;

    public function __construct(
        private readonly CreditScoringService $creditScoring,
    ) {}

    public function listProducts(?string $type = null): iterable
    {
        $q = LoanProduct::where('is_active', true);
        if ($type) $q->where('product_type', $type);
        return $q->get();
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
        $user = User::findOrFail($userId);

        if ($amount < $product->min_amount || $amount > $product->max_amount) {
            throw new \InvalidArgumentException("Amount outside product range: {$product->min_amount}-{$product->max_amount}");
        }

        $score = $this->creditScoring->calculate($user);

        if (!$this->creditScoring->meetsThreshold($score->score)) {
            throw new CreditScoreTooLowException($score->score, self::MIN_SCORE_THRESHOLD);
        }

        $markup = $product->product_type === 'qard_hasan'
            ? 0
            : $amount * ($product->interest_rate / 100) * ($termDays / 365);

        $totalRepayable = $amount + (int) round($markup);
        $status = $product->product_type === 'bnpl' ? LoanStatus::APPROVED : LoanStatus::PENDING;

        $loan = Loan::create([
            'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'loan_product_id' => $productId,
            'product_type' => $product->product_type,
            'principal' => $amount,
            'total_repayable' => $totalRepayable,
            'outstanding_balance' => $totalRepayable,
            'interest_rate' => $product->interest_rate,
            'late_penalty_rate' => $product->late_penalty_rate,
            'term_days' => $termDays,
            'status' => $status->value,
            'purpose' => $purpose,
            'credit_score' => $score->score,
        ]);

        $this->generateInstallments($loan, $totalRepayable, $termDays, $product);

        return $loan;
    }

    public function approve(string $loanId): Loan
    {
        $loan = Loan::findOrFail($loanId);
        $loan->update([
            'status' => LoanStatus::APPROVED->value,
            'approved_at' => now(),
        ]);
        return $loan;
    }

    public function disburse(string $loanId): Loan
    {
        $loan = Loan::findOrFail($loanId);
        if ($loan->status !== LoanStatus::APPROVED->value) throw new LoanNotApprovedException;
        $loan->update([
            'status' => LoanStatus::DISBURSED->value,
            'disbursed_at' => now(),
        ]);
        return $loan;
    }

    public function repay(string $loanId, int $amount, ?string $method = 'wallet'): LoanRepayment
    {
        $loan = Loan::findOrFail($loanId);
        $installment = LoanRepayment::where('loan_id', $loanId)
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('installment_number')
            ->first();

        if (!$installment) throw new LoanAlreadyCompletedException;
        $due = $installment->amount - $installment->paid_amount;
        if ($amount > $due) throw new RepaymentAmountExceedsBalanceException;

        $paidAmount = $installment->paid_amount + $amount;
        $installment->update([
            'paid_amount' => $paidAmount,
            'status' => $paidAmount >= $installment->amount ? 'paid' : 'partial',
            'paid_at' => $paidAmount >= $installment->amount ? now() : null,
            'payment_method' => $method,
        ]);

        $loan->decrement('outstanding_balance', $amount);
        if ($loan->outstanding_balance <= 0) {
            $loan->update(['status' => LoanStatus::COMPLETED->value, 'completed_at' => now()]);
        }

        return $installment;
    }

    public function calculateLatePenalty(LoanRepayment $installment): int
    {
        if (!now()->isAfter($installment->due_date)) return 0;
        $overdueDays = now()->diffInDays($installment->due_date);
        $loan = $installment->loan;
        $rate = $loan->late_penalty_rate ?? 0;
        return (int) floor($installment->amount * ($rate / 100) * min($overdueDays, 30) / 30);
    }

    public function userLoans(string $userId): iterable
    {
        return Loan::where('user_id', $userId)
            ->with('repayments')
            ->orderByDesc('created_at')
            ->get();
    }

    public function adminDashboard(): array
    {
        return [
            'total_loans' => Loan::count(),
            'pending_review' => Loan::where('status', 'pending')->count(),
            'active_loans' => Loan::whereIn('status', ['disbursed', 'active'])->count(),
            'completed_loans' => Loan::where('status', 'completed')->count(),
            'defaulted_loans' => Loan::where('status', 'defaulted')->count(),
            'total_disbursed' => Loan::sum('principal'),
            'total_outstanding' => Loan::sum('outstanding_balance'),
            'npl_ratio' => $this->calculateNplRatio(),
            'by_product' => LoanProduct::withCount(['loans'])->get()->map(fn($p) => [
                'name' => $p->name, 'name_ar' => $p->name_ar, 'count' => $p->loans_count,
            ]),
        ];
    }

    public function bnplCheckout(string $userId, string $merchantId, int $amount, string $merchantTxId): Loan
    {
        $product = LoanProduct::where('product_type', 'bnpl')->where('is_active', true)->first();
        if (!$product) throw new \RuntimeException('No BNPL product configured');

        $installments = $product->bnpl_installments ?? [3];
        $termDays = $installments[0] * 30;

        $loan = $this->apply($userId, $product->id, $amount, $termDays, "BNPL merchant:{$merchantId} tx:{$merchantTxId}");

        $this->approve($loan->id);
        $this->disburse($loan->id);

        return $loan;
    }

    public function schedule(string $loanId): iterable
    {
        return LoanRepayment::where('loan_id', $loanId)
            ->orderBy('installment_number')
            ->get();
    }

    public function loansByStatus(string $status, int $perPage = 15): iterable
    {
        return Loan::where('status', $status)
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    private function generateInstallments(Loan $loan, int $total, int $days, LoanProduct $product): void
    {
        $count = $product->product_type === 'bnpl'
            ? (min($product->bnpl_installments)[0] ?? 3)
            : max(1, (int) ceil($days / 30));

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
                'due_date' => $now->copy()->addDays((int) ($days / $count) * $i),
                'status' => 'pending',
            ]);
        }
    }

    private function calculateNplRatio(): float
    {
        $total = Loan::sum('outstanding_balance');
        if ($total <= 0) return 0;
        $overdue = Loan::where('status', 'defaulted')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['disbursed', 'active'])
                  ->whereHas('repayments', fn($r) => $r->where('status', 'pending')->where('due_date', '<', now()));
            })
            ->sum('outstanding_balance');
        return round(($overdue / $total) * 100, 2);
    }
}
