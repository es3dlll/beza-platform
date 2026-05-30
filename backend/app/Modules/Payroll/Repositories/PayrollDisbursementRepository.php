<?php

declare(strict_types=1);

namespace Modules\Payroll\Repositories;

use Modules\Payroll\Models\PayrollDisbursement;

final class PayrollDisbursementRepository
{
    public function create(array $data): PayrollDisbursement
    {
        return PayrollDisbursement::create($data);
    }

    public function update(string $id, array $data): PayrollDisbursement
    {
        $d = PayrollDisbursement::findOrFail($id);
        $d->update($data);
        return $d->fresh();
    }

    public function findByBatch(string $batchId): iterable
    {
        return PayrollDisbursement::where('payroll_batch_id', $batchId)->get();
    }

    public function countCompletedByBatch(string $batchId): int
    {
        return PayrollDisbursement::where('payroll_batch_id', $batchId)
            ->where('status', 'completed')
            ->count();
    }

    public function countFailedByBatch(string $batchId): int
    {
        return PayrollDisbursement::where('payroll_batch_id', $batchId)
            ->where('status', 'failed')
            ->count();
    }
}
