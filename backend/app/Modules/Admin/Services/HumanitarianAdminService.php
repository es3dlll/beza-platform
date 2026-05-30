<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\DB;
use Modules\Humanitarian\Models\HumanitarianDisbursement;
use Modules\Humanitarian\Models\HumanitarianProgram;
use Modules\Humanitarian\Services\HumanitarianService;

final class HumanitarianAdminService
{
    public function __construct(
        private readonly HumanitarianService $humanitarian,
    ) {}

    public function dashboard(): array
    {
        $activePrograms = HumanitarianProgram::where('status', 'active')->count();
        $totalBeneficiaries = HumanitarianDisbursement::whereNotNull('beneficiary_id')->distinct('beneficiary_id')->count('beneficiary_id');
        $totalBudget = HumanitarianProgram::sum('total_budget');
        $totalRemaining = HumanitarianProgram::sum('remaining_budget');
        $budgetUtilization = $totalBudget > 0 ? round((($totalBudget - $totalRemaining) / $totalBudget) * 100, 2) : 0;
        $disbursements = HumanitarianDisbursement::where('status', 'disbursed')
            ->whereNotNull('disbursed_at')
            ->get(['created_at', 'disbursed_at']);
        $slaSeconds = $disbursements->isNotEmpty()
            ? (int) $disbursements->avg(fn($d) => $d->disbursed_at->diffInSeconds($d->created_at))
            : 0;

        return [
            'active_programs' => $activePrograms,
            'total_beneficiaries' => $totalBeneficiaries,
            'budget_utilization_percent' => $budgetUtilization,
            'disbursement_sla_seconds' => $slaSeconds,
        ];
    }

    public function listActivePrograms(): iterable
    {
        return HumanitarianProgram::where('status', 'active')->with('organization')->get();
    }

    public function programDetail(string $id): array
    {
        $program = HumanitarianProgram::with('organization')->findOrFail($id);
        $disbursements = HumanitarianDisbursement::where('program_id', $id)
            ->orderByDesc('created_at')->limit(20)->get();
        return [
            'program' => $program,
            'recent_disbursements' => $disbursements,
        ];
    }

    public function approveProgram(string $id): void
    {
        HumanitarianProgram::where('id', $id)->update(['status' => 'active']);
    }

    public function suspendProgram(string $id, string $reason): void
    {
        HumanitarianProgram::where('id', $id)->update(['status' => 'suspended']);
    }

    public function budgetAlerts(): iterable
    {
        return HumanitarianProgram::where('status', 'active')
            ->where(DB::raw('(total_budget - remaining_budget) * 100.0 / NULLIF(total_budget, 0)'), '>', 80)
            ->get();
    }

    public function donorReport(string $programId, string $format = 'json'): array
    {
        $program = HumanitarianProgram::with('organization')->findOrFail($programId);
        $beneficiaryCount = HumanitarianDisbursement::where('program_id', $programId)
            ->whereNotNull('beneficiary_id')->distinct('beneficiary_id')->count('beneficiary_id');
        $totalDisbursed = (int) HumanitarianDisbursement::where('program_id', $programId)
            ->where('status', 'disbursed')->sum('amount');

        return [
            'program' => $program->name,
            'organization' => $program->organization?->name,
            'total_budget' => $program->total_budget,
            'total_disbursed' => $totalDisbursed,
            'remaining_budget' => $program->remaining_budget,
            'beneficiary_count' => $beneficiaryCount,
            'utilization_percent' => $program->total_budget > 0
                ? round(($totalDisbursed / $program->total_budget) * 100, 2) : 0,
            'start_date' => $program->starts_at?->format('Y-m-d'),
            'end_date' => $program->ends_at?->format('Y-m-d'),
        ];
    }
}
