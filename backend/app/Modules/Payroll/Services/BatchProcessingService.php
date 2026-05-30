<?php

declare(strict_types=1);

namespace Modules\Payroll\Services;

use Modules\Payroll\Enums\DisbursementStatus;
use Modules\Payroll\Enums\PayrollBatchStatus;
use Modules\Payroll\Events\PayrollDisbursementCompleted;
use Modules\Payroll\Events\PayrollDisbursementFailed;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollDisbursement;
use Modules\Payroll\Repositories\PayrollBatchRepository;
use Modules\Payroll\Repositories\PayrollDisbursementRepository;

final class BatchProcessingService
{
    public function __construct(
        private readonly PayrollBatchRepository $batchRepository,
        private readonly PayrollDisbursementRepository $disbursementRepository,
    ) {}

    public function process(PayrollBatch $batch): void
    {
        $this->batchRepository->update($batch->id, [
            'status' => PayrollBatchStatus::PROCESSING->value,
        ]);

        $disbursements = $this->disbursementRepository->findByBatch($batch->id);
        $failed = 0;
        $completed = 0;

        foreach ($disbursements as $disbursement) {
            try {
                $this->processDisbursement($disbursement);
                $completed++;
            } catch (\Exception $e) {
                $failed++;
                PayrollDisbursementFailed::dispatch(
                    $disbursement->id,
                    $batch->id,
                    $disbursement->employee_phone,
                    $disbursement->amount,
                    $e->getMessage(),
                );
            }
        }

        $newStatus = match (true) {
            $failed === 0 && $completed > 0 => PayrollBatchStatus::COMPLETED,
            $completed > 0 && $failed > 0 => PayrollBatchStatus::PARTIALLY_FAILED,
            $failed > 0 && $completed === 0 => PayrollBatchStatus::FAILED,
            default => PayrollBatchStatus::FAILED,
        };

        $this->batchRepository->update($batch->id, [
            'status' => $newStatus->value,
            'processed_at' => now(),
        ]);
    }

    private function processDisbursement(PayrollDisbursement $disbursement): void
    {
        $this->disbursementRepository->update($disbursement->id, [
            'status' => DisbursementStatus::PROCESSING->value,
        ]);

        // Simulate wallet disbursement — in production this calls WalletService::transfer()
        $mockTransactionId = 'TXN-' . strtoupper(\Illuminate\Support\Str::random(12));

        $this->disbursementRepository->update($disbursement->id, [
            'status' => DisbursementStatus::COMPLETED->value,
            'wallet_transaction_id' => $mockTransactionId,
            'processed_at' => now(),
        ]);

        PayrollDisbursementCompleted::dispatch(
            $disbursement->id,
            $disbursement->payroll_batch_id,
            $disbursement->employee_phone,
            $disbursement->amount,
            $mockTransactionId,
        );
    }
}
