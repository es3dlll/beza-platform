<?php

declare(strict_types=1);

namespace Modules\Payroll\Repositories;

use Modules\Payroll\Models\Employer;

final class EmployerRepository
{
    public function create(array $data): Employer
    {
        return Employer::create($data);
    }

    public function findById(string $id): ?Employer
    {
        return Employer::find($id);
    }

    public function findByUser(string $userId): ?Employer
    {
        return Employer::where('user_id', $userId)->first();
    }

    public function update(string $id, array $data): Employer
    {
        $employer = Employer::findOrFail($id);
        $employer->update($data);
        return $employer->fresh();
    }

    public function usedMonthlyPayroll(string $employerId, string $periodMonth): int
    {
        return Employer::query()
            ->whereHas('payrollBatches', function ($q) use ($periodMonth) {
                $q->where('period_month', $periodMonth)
                  ->whereIn('status', ['approved', 'processing', 'completed', 'partially_failed']);
            })
            ->where('id', $employerId)
            ->value('used_monthly_payroll') ?? 0;
    }
}
