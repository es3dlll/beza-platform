<?php

declare(strict_types=1);

namespace Modules\Payroll\Repositories;

use Modules\Payroll\Models\PayrollBatch;

class PayrollBatchRepository
{
    public function create(array $data): PayrollBatch
    {
        return PayrollBatch::create($data);
    }

    public function findById(string $id): ?PayrollBatch
    {
        return PayrollBatch::find($id);
    }

    public function update(string $id, array $data): PayrollBatch
    {
        $batch = PayrollBatch::findOrFail($id);
        $batch->update($data);
        return $batch->fresh();
    }

    public function findByEmployer(string $employerId, int $perPage = 15): iterable
    {
        return PayrollBatch::where('employer_id', $employerId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function generateReference(): string
    {
        return 'PRL-' . strtoupper(\Illuminate\Support\Str::random(8));
    }
}
