<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Modules\Financing\Models\Loan;
use Modules\Financing\Models\LoanProduct;
use Modules\Financing\Services\FinancingService;

final class FinancingAdminService
{
    public function __construct(
        private readonly FinancingService $financing,
    ) {}

    public function dashboard(): array
    {
        $totalDisbursed = Loan::whereIn('status', ['disbursed', 'completed', 'active', 'defaulted'])->sum('principal');
        $active = Loan::whereIn('status', ['disbursed', 'active'])->count();

        $nplTotal = Loan::whereIn('status', ['disbursed', 'active', 'defaulted'])->sum('outstanding_balance');
        $defaultedOut = Loan::where('status', 'defaulted')->sum('outstanding_balance');
        $nplRatio = $nplTotal > 0 ? round(($defaultedOut / $nplTotal) * 100, 2) : 0;

        $byProduct = LoanProduct::withCount('loans')->get()->map(fn($p) => [
            'id' => $p->id, 'name' => $p->name, 'count' => $p->loans_count,
        ]);

        $byStatus = Loan::selectRaw("status, COUNT(*) as count")->groupBy('status')->pluck('count', 'status');

        return [
            'total_loans_disbursed' => $totalDisbursed,
            'active_loans' => $active,
            'npl_ratio' => $nplRatio,
            'portfolio_by_product' => $byProduct,
            'loans_by_status' => $byStatus,
        ];
    }

    public function pendingApprovals(): iterable
    {
        return Loan::with('user')->where('status', 'under_review')->orderByDesc('created_at')->get();
    }

    public function approveLoan(string $id): void
    {
        $this->financing->approve($id);
    }

    public function rejectLoan(string $id, string $reason): void
    {
        Loan::where('id', $id)->update(['status' => 'cancelled', 'rejection_reason' => $reason]);
    }

    public function loanDetail(string $id): array
    {
        $loan = Loan::with(['user', 'repayments', 'product'])->findOrFail($id);
        return $loan->toArray();
    }

    public function writeOff(string $id): void
    {
        Loan::where('id', $id)->update(['status' => 'defaulted', 'defaulted_at' => now()]);
    }

    public function listLoans(?string $status, int $perPage): iterable
    {
        return $this->financing->loansByStatus($status ?? 'pending', $perPage);
    }

    public function productConfig(): array
    {
        return LoanProduct::where('is_active', true)->get()->toArray();
    }

    public function updateProductConfig(string $id, array $data): void
    {
        LoanProduct::where('id', $id)->update($data);
    }
}
