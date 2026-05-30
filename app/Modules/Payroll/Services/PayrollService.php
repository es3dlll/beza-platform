<?php

declare(strict_types=1);

namespace Modules\Payroll\Services;

use Illuminate\Support\Str;
use Modules\Payroll\DTOs\CreatePayrollBatchDto;
use Modules\Payroll\DTOs\RegisterEmployerDto;
use Modules\Payroll\Enums\EmployerStatus;
use Modules\Payroll\Enums\PayrollBatchStatus;
use Modules\Payroll\Enums\DisbursementStatus;
use Modules\Payroll\Events\EmployerRegistered;
use Modules\Payroll\Events\PayrollBatchCreated;
use Modules\Payroll\Events\PayrollBatchApproved;
use Modules\Payroll\Exceptions\EmployerNotFoundException;
use Modules\Payroll\Exceptions\EmployerSuspendedException;
use Modules\Payroll\Exceptions\PayrollBatchNotFoundException;
use Modules\Payroll\Exceptions\InsufficientBalanceException;
use Modules\Payroll\Exceptions\PayrollValidationException;
use Modules\Payroll\Models\Employer;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Repositories\EmployerRepository;
use Modules\Payroll\Repositories\EmployeeRecordRepository;
use Modules\Payroll\Repositories\PayrollBatchRepository;
use Modules\Payroll\Repositories\PayrollDisbursementRepository;

class PayrollService
{
    public function __construct(
        private readonly EmployerRepository $employerRepository,
        private readonly EmployeeRecordRepository $employeeRecordRepository,
        private readonly PayrollBatchRepository $batchRepository,
        private readonly PayrollDisbursementRepository $disbursementRepository,
        private readonly CsvParserService $csvParser,
        private readonly BatchProcessingService $batchProcessor,
    ) {}

    public function registerEmployer(RegisterEmployerDto $dto): Employer
    {
        $employer = $this->employerRepository->create([
            'id' => (string) Str::ulid(),
            'user_id' => $dto->userId,
            'company_name' => $dto->companyName,
            'company_name_ar' => $dto->companyNameAr,
            'phone' => $dto->phone,
            'governorate' => $dto->governorate,
            'city' => $dto->city,
            'commercial_registration' => $dto->commercialRegistration,
            'tax_number' => $dto->taxNumber,
            'email' => $dto->email,
            'address' => $dto->address,
            'status' => EmployerStatus::PENDING->value,
        ]);

        EmployerRegistered::dispatch($employer->id, $dto->userId, $dto->companyName);
        return $employer;
    }

    public function approveEmployer(string $id, string $approvedBy): Employer
    {
        $employer = $this->findEmployerOrFail($id);
        return $this->employerRepository->update($id, [
            'status' => EmployerStatus::ACTIVE->value,
            'approved_at' => now(),
        ]);
    }

    public function suspendEmployer(string $id): Employer
    {
        $this->findEmployerOrFail($id);
        return $this->employerRepository->update($id, [
            'status' => EmployerStatus::SUSPENDED->value,
        ]);
    }

    public function findEmployerByUser(string $userId): ?Employer
    {
        return $this->employerRepository->findByUser($userId);
    }

    public function createBatch(CreatePayrollBatchDto $dto): PayrollBatch
    {
        $employer = $this->findEmployerOrFail($dto->employerId);
        $this->ensureActive($employer);

        $totalAmount = 0;
        $disbursements = [];

        foreach ($dto->employees as $emp) {
            $amount = (int) ($emp['amount'] ?? 0);
            if ($amount <= 0) {
                throw new PayrollValidationException("Invalid amount for employee: {$emp['employee_name']}");
            }
            $totalAmount += $amount;

            // Upsert employee record
            $this->employeeRecordRepository->upsertByPhone($dto->employerId, [
                'full_name' => $emp['employee_name'],
                'phone' => $emp['phone'],
                'base_salary' => $amount,
            ]);

            $disbursements[] = $emp;
        }

        $batch = $this->batchRepository->create([
            'id' => (string) Str::ulid(),
            'employer_id' => $dto->employerId,
            'batch_reference' => $this->batchRepository->generateReference(),
            'total_employees' => count($disbursements),
            'total_amount' => $totalAmount,
            'status' => PayrollBatchStatus::PENDING->value,
            'period_month' => $dto->periodMonth,
            'notes' => $dto->notes,
        ]);

        // Create disbursement records
        foreach ($disbursements as $emp) {
            $this->disbursementRepository->create([
                'id' => (string) Str::ulid(),
                'payroll_batch_id' => $batch->id,
                'employer_id' => $dto->employerId,
                'employee_name' => $emp['employee_name'],
                'employee_phone' => $emp['phone'],
                'amount' => (int) $emp['amount'],
                'status' => DisbursementStatus::PENDING->value,
            ]);
        }

        PayrollBatchCreated::dispatch($batch->id, $dto->employerId, count($disbursements), $totalAmount);
        return $batch;
    }

    public function createBatchFromCsv(string $employerId, string $csvContent, string $periodMonth, ?string $notes = null): PayrollBatch
    {
        $employer = $this->findEmployerOrFail($employerId);
        $this->ensureActive($employer);

        $employees = $this->csvParser->parse($csvContent);

        $dto = new CreatePayrollBatchDto(
            employerId: $employerId,
            periodMonth: $periodMonth,
            notes: $notes,
            employees: $employees,
        );

        return $this->createBatch($dto);
    }

    public function approveBatch(string $batchId, string $approvedBy): PayrollBatch
    {
        $batch = $this->findBatchOrFail($batchId);
        $employer = $this->findEmployerOrFail($batch->employer_id);
        $this->ensureActive($employer);

        // Check monthly limit
        $used = $employer->used_monthly_payroll;
        $remaining = $employer->monthly_payroll_limit - $used;
        if ($batch->total_amount > $remaining) {
            throw new InsufficientBalanceException($batch->total_amount, $remaining);
        }

        $batch = $this->batchRepository->update($batchId, [
            'status' => PayrollBatchStatus::APPROVED->value,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        PayrollBatchApproved::dispatch($batchId, $employer->id, $batch->total_amount);
        return $batch;
    }

    public function processBatch(string $batchId): PayrollBatch
    {
        $batch = $this->findBatchOrFail($batchId);

        if ($batch->status !== PayrollBatchStatus::APPROVED->value) {
            throw new PayrollValidationException('Batch must be approved before processing');
        }

        $this->batchProcessor->process($batch);
        return $this->batchRepository->findById($batchId);
    }

    public function findEmployerOrFail(string $id): Employer
    {
        $employer = $this->employerRepository->findById($id);
        if (!$employer) {
            throw new EmployerNotFoundException($id);
        }
        return $employer;
    }

    public function findBatchOrFail(string $id): PayrollBatch
    {
        $batch = $this->batchRepository->findById($id);
        if (!$batch) {
            throw new PayrollBatchNotFoundException($id);
        }
        return $batch;
    }

    public function ensureActive(Employer $employer): void
    {
        if ($employer->status !== EmployerStatus::ACTIVE->value) {
            throw new EmployerSuspendedException($employer->id);
        }
    }
}
