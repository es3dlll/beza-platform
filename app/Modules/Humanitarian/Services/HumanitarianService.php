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

class HumanitarianService
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
}
