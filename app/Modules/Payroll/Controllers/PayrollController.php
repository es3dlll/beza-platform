<?php

declare(strict_types=1);

namespace Modules\Payroll\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payroll\DTOs\RegisterEmployerDto;
use Modules\Payroll\DTOs\CreatePayrollBatchDto;
use Modules\Payroll\Exceptions\EmployerNotFoundException;
use Modules\Payroll\Exceptions\PayrollBatchNotFoundException;
use Modules\Payroll\Exceptions\InsufficientBalanceException;
use Modules\Payroll\Exceptions\PayrollValidationException;
use Modules\Payroll\Http\Requests\RegisterEmployerRequest;
use Modules\Payroll\Http\Requests\CreateBatchRequest;
use Modules\Payroll\Http\Requests\UploadCsvRequest;
use Modules\Payroll\Http\Requests\ApproveBatchRequest;
use Modules\Payroll\Services\PayrollService;
use Modules\Payroll\Repositories\PayrollBatchRepository;
use Modules\Payroll\Repositories\PayrollDisbursementRepository;
use Modules\Payroll\Repositories\EmployeeRecordRepository;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $payrollService,
        private readonly PayrollBatchRepository $batchRepository,
        private readonly PayrollDisbursementRepository $disbursementRepository,
        private readonly EmployeeRecordRepository $employeeRepository,
    ) {}

    public function register(RegisterEmployerRequest $request): JsonResponse
    {
        $dto = new RegisterEmployerDto(
            userId: $request->user()->id,
            companyName: $request->input('company_name'),
            companyNameAr: $request->input('company_name_ar'),
            phone: $request->input('phone'),
            governorate: $request->input('governorate'),
            city: $request->input('city'),
            commercialRegistration: $request->input('commercial_registration'),
            taxNumber: $request->input('tax_number'),
            email: $request->input('email'),
            address: $request->input('address'),
        );

        $employer = $this->payrollService->registerEmployer($dto);
        return response()->json(['data' => $employer], 201);
    }

    public function myEmployer(Request $request): JsonResponse
    {
        $employer = $this->payrollService->findEmployerByUser($request->user()->id);
        if (!$employer) {
            return response()->json(['error' => 'EMPLOYER_NOT_FOUND'], 404);
        }
        return response()->json(['data' => $employer]);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $employer = $this->payrollService->approveEmployer($id, $request->user()->id);
        return response()->json(['data' => $employer]);
    }

    public function suspend(string $id): JsonResponse
    {
        $employer = $this->payrollService->suspendEmployer($id);
        return response()->json(['data' => $employer]);
    }

    public function createBatch(CreateBatchRequest $request): JsonResponse
    {
        $dto = new CreatePayrollBatchDto(
            employerId: $request->input('employer_id'),
            periodMonth: $request->input('period_month'),
            notes: $request->input('notes'),
            employees: $request->input('employees'),
        );

        try {
            $batch = $this->payrollService->createBatch($dto);
        } catch (PayrollValidationException $e) {
            return response()->json(['error' => 'PAYROLL_VALIDATION_ERROR', 'reason' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $batch], 201);
    }

    public function uploadCsv(UploadCsvRequest $request): JsonResponse
    {
        try {
            $batch = $this->payrollService->createBatchFromCsv(
                employerId: $request->input('employer_id'),
                csvContent: $request->input('csv_content'),
                periodMonth: $request->input('period_month'),
                notes: $request->input('notes'),
            );
        } catch (PayrollValidationException $e) {
            return response()->json(['error' => 'PAYROLL_CSV_ERROR', 'reason' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $batch], 201);
    }

    public function approveBatch(ApproveBatchRequest $request, string $id): JsonResponse
    {
        try {
            $batch = $this->payrollService->approveBatch($id, $request->user()->id);
        } catch (InsufficientBalanceException $e) {
            return response()->json(['error' => 'INSUFFICIENT_BALANCE', 'reason' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $batch]);
    }

    public function processBatch(string $id): JsonResponse
    {
        try {
            $batch = $this->payrollService->processBatch($id);
        } catch (PayrollValidationException $e) {
            return response()->json(['error' => 'PAYROLL_PROCESS_ERROR', 'reason' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $batch]);
    }

    public function listBatches(Request $request): JsonResponse
    {
        $batches = $this->batchRepository->findByEmployer(
            $request->input('employer_id'),
            (int) $request->input('per_page', 15),
        );

        return response()->json(['data' => $batches]);
    }

    public function showBatch(string $id): JsonResponse
    {
        $batch = $this->payrollService->findBatchOrFail($id);
        $disbursements = $this->disbursementRepository->findByBatch($id);
        return response()->json(['data' => $batch, 'disbursements' => $disbursements]);
    }

    public function listEmployees(Request $request, string $employerId): JsonResponse
    {
        $employees = $this->employeeRepository->findByEmployer($employerId);
        return response()->json(['data' => $employees]);
    }
}
