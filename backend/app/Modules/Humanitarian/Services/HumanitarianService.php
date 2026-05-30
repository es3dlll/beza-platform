<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Services;

use Illuminate\Support\Str;
use Modules\Humanitarian\Models\HumanitarianOrganization;
use Modules\Humanitarian\Models\HumanitarianProgram;
use Modules\Humanitarian\Models\HumanitarianDisbursement;
use Modules\Humanitarian\Enums\DisbursementStatus;
use Modules\Humanitarian\Exceptions\OrganizationNotFoundException;
use Modules\Humanitarian\Exceptions\ProgramNotFoundException;
use Modules\Humanitarian\Exceptions\InsufficientBudgetException;

final class HumanitarianService
{
    public function listOrganizations(): iterable { return HumanitarianOrganization::where('is_active', true)->get(); }

    public function listPrograms(?string $orgId = null): iterable
    {
        $q = HumanitarianProgram::where('status', 'active');
        if ($orgId) $q->where('organization_id', $orgId);
        return $q->get();
    }

    public function createDisbursement(string $programId, string $userId, string $type, int $amount, ?string $beneficiaryId = null): HumanitarianDisbursement
    {
        $program = HumanitarianProgram::find($programId);
        if (!$program) throw new ProgramNotFoundException($programId);
        if ($program->remaining_budget < $amount) throw new InsufficientBudgetException;
        $disbursement = HumanitarianDisbursement::create([
            'id' => (string) Str::ulid(), 'program_id' => $programId, 'user_id' => $userId,
            'beneficiary_id' => $beneficiaryId, 'amount' => $amount, 'type' => $type,
            'status' => DisbursementStatus::APPROVED->value, 'reference_number' => 'HUM-' . strtoupper(Str::random(10)),
        ]);
        $program->decrement('remaining_budget', $amount);
        $disbursement->update(['status' => DisbursementStatus::DISBURSED->value, 'disbursed_at' => now()]);
        return $disbursement;
    }

    public function history(): iterable { return HumanitarianDisbursement::with('program')->orderByDesc('created_at')->get(); }

    public function batchDisburse(string $programId, array $beneficiaries): array
    {
        $program = HumanitarianProgram::find($programId);
        if (!$program) throw new ProgramNotFoundException($programId);

        $total = count($beneficiaries);
        $succeeded = 0;
        $failed = 0;
        $errors = [];
        $batchId = 'BATCH-' . strtoupper(Str::random(12));

        foreach (array_chunk($beneficiaries, 100) as $chunkIndex => $chunk) {
            foreach ($chunk as $index => $beneficiary) {
                try {
                    if ($program->remaining_budget < $beneficiary['amount']) {
                        throw new InsufficientBudgetException;
                    }

                    $disbursement = HumanitarianDisbursement::create([
                        'id' => (string) Str::ulid(),
                        'program_id' => $programId,
                        'user_id' => $beneficiary['user_id'],
                        'amount' => $beneficiary['amount'],
                        'type' => $beneficiary['type'],
                        'status' => DisbursementStatus::APPROVED->value,
                        'reference_number' => 'HUM-' . strtoupper(Str::random(10)),
                        'disbursement_batch_id' => $batchId,
                    ]);

                    $program->decrement('remaining_budget', $beneficiary['amount']);
                    $disbursement->update(['status' => DisbursementStatus::DISBURSED->value, 'disbursed_at' => now()]);
                    $succeeded++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = [
                        'index' => $chunkIndex * 100 + $index,
                        'user_id' => $beneficiary['user_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return ['total' => $total, 'succeeded' => $succeeded, 'failed' => $failed, 'errors' => $errors];
    }

    public function ngoDashboard(string $organizationId): array
    {
        $programs = HumanitarianProgram::where('organization_id', $organizationId)->get();
        $programIds = $programs->pluck('id');

        $disbursedAmount = HumanitarianDisbursement::whereIn('program_id', $programIds)
            ->where('status', DisbursementStatus::DISBURSED->value)
            ->sum('amount');

        $activeBeneficiaries = HumanitarianDisbursement::whereIn('program_id', $programIds)
            ->where('status', DisbursementStatus::DISBURSED->value)
            ->whereNotNull('beneficiary_id')
            ->distinct('beneficiary_id')
            ->count('beneficiary_id');

        $recentDisbursements = HumanitarianDisbursement::whereIn('program_id', $programIds)
            ->with('program')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'total_programs' => $programs->count(),
            'total_budget' => $programs->sum('total_budget'),
            'remaining_budget' => $programs->sum('remaining_budget'),
            'disbursed_amount' => $disbursedAmount,
            'active_beneficiaries' => $activeBeneficiaries,
            'recent_disbursements' => $recentDisbursements,
        ];
    }

    public function agentPickupCode(string $disbursementId): string
    {
        $disbursement = HumanitarianDisbursement::find($disbursementId);
        if (!$disbursement) throw new \RuntimeException("Disbursement not found: {$disbursementId}");

        $code = (string) random_int(100000, 999999);
        $disbursement->update(['pickup_code' => $code]);

        return $code;
    }

    public function donorReport(string $organizationId): array
    {
        $programs = HumanitarianProgram::where('organization_id', $organizationId)->get();
        $programIds = $programs->pluck('id');

        $beneficiaryCounts = HumanitarianDisbursement::whereIn('program_id', $programIds)
            ->where('status', DisbursementStatus::DISBURSED->value)
            ->whereNotNull('beneficiary_id')
            ->groupBy('program_id')
            ->selectRaw('program_id, COUNT(DISTINCT beneficiary_id) as count')
            ->pluck('count', 'program_id');

        return $programs->map(function ($program) use ($beneficiaryCounts): array {
            return [
                'program_name' => $program->name,
                'budget' => $program->total_budget,
                'disbursed' => $program->total_budget - $program->remaining_budget,
                'remaining' => $program->remaining_budget,
                'beneficiary_count' => $beneficiaryCounts[$program->id] ?? 0,
                'start_date' => $program->starts_at?->format('Y-m-d'),
                'end_date' => $program->ends_at?->format('Y-m-d'),
            ];
        })->toArray();
    }

    public function ofacScreen(array $beneficiaryIds): array
    {
        $flagged = [];
        foreach ($beneficiaryIds as $id) {
            if (str_starts_with((string) $id, 'SAN')) {
                $flagged[] = $id;
            }
        }

        return ['screened' => count($beneficiaryIds), 'flagged' => $flagged];
    }
}
