<?php

declare(strict_types=1);

namespace Modules\Payroll\Repositories;

use Modules\Payroll\Models\EmployeeRecord;

final class EmployeeRecordRepository
{
    public function create(array $data): EmployeeRecord
    {
        return EmployeeRecord::create($data);
    }

    public function findByEmployer(string $employerId): iterable
    {
        return EmployeeRecord::where('employer_id', $employerId)
            ->where('is_active', true)
            ->get();
    }

    public function findByPhone(string $employerId, string $phone): ?EmployeeRecord
    {
        return EmployeeRecord::where('employer_id', $employerId)
            ->where('phone', $phone)
            ->first();
    }

    public function upsertByPhone(string $employerId, array $data): EmployeeRecord
    {
        $existing = $this->findByPhone($employerId, $data['phone']);
        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }
        return $this->create(array_merge(
            ['id' => (string) \Illuminate\Support\Str::ulid(), 'employer_id' => $employerId],
            $data,
        ));
    }
}
