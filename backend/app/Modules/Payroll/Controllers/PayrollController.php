<?php

declare(strict_types=1);

namespace Modules\Payroll\Controllers;

use App\Support\ApiResponse;
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
use Modules\Payroll\Services\SalaryCertificateService;

final class PayrollController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PayrollService $payrollService,
        private readonly PayrollBatchRepository $batchRepository,
        private readonly PayrollDisbursementRepository $disbursementRepository,
        private readonly EmployeeRecordRepository $employeeRepository,
        private readonly SalaryCertificateService $certificateService,
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
        return $this->respondCreated($employer);
    }

    public function myEmployer(Request $request): JsonResponse
    {
        $employer = $this->payrollService->findEmployerByUser($request->user()->id);
        if (!$employer) {
            return $this->respondError('EMPLOYER_NOT_FOUND', null, null, 404);
        }
        return $this->respond($employer);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $employer = $this->payrollService->approveEmployer($id, $request->user()->id);
        return $this->respond($employer);
    }

    public function suspend(string $id): JsonResponse
    {
        $employer = $this->payrollService->suspendEmployer($id);
        return $this->respond($employer);
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
            return $this->respondError('PAYROLL_VALIDATION_ERROR', $e->getMessage(), null, 422);
        }

        return $this->respondCreated($batch);
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
            return $this->respondError('PAYROLL_CSV_ERROR', $e->getMessage(), null, 422);
        }

        return $this->respondCreated($batch);
    }

    public function approveBatch(ApproveBatchRequest $request, string $id): JsonResponse
    {
        try {
            $batch = $this->payrollService->approveBatch($id, $request->user()->id);
        } catch (InsufficientBalanceException $e) {
            return $this->respondError('INSUFFICIENT_BALANCE', $e->getMessage(), null, 422);
        }

        return $this->respond($batch);
    }

    public function processBatch(string $id): JsonResponse
    {
        try {
            $batch = $this->payrollService->processBatch($id);
        } catch (PayrollValidationException $e) {
            return $this->respondError('PAYROLL_PROCESS_ERROR', $e->getMessage(), null, 422);
        }

        return $this->respond($batch);
    }

    public function listBatches(Request $request): JsonResponse
    {
        $batches = $this->batchRepository->findByEmployer(
            $request->input('employer_id'),
            (int) $request->input('per_page', 15),
        );

        return $this->respond($batches);
    }

    public function showBatch(string $id): JsonResponse
    {
        $batch = $this->payrollService->findBatchOrFail($id);
        $disbursements = $this->disbursementRepository->findByBatch($id);
        return $this->respond(['batch' => $batch, 'disbursements' => $disbursements]);
    }

    public function listEmployees(Request $request, string $employerId): JsonResponse
    {
        $employees = $this->employeeRepository->findByEmployer($employerId);
        return $this->respond($employees);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $employer = $this->payrollService->findEmployerByUser($request->user()->id);
        if (!$employer) {
            return $this->respondError('EMPLOYER_NOT_FOUND', null, null, 404);
        }

        $batches = $this->batchRepository->findByEmployer($employer->id, 5);
        $stats = [
            'total_batches' => \Modules\Payroll\Models\PayrollBatch::where('employer_id', $employer->id)->count(),
            'total_spent' => \Modules\Payroll\Models\PayrollBatch::where('employer_id', $employer->id)
                ->whereIn('status', ['completed', 'partially_failed'])
                ->sum('total_amount'),
            'total_employees' => \Modules\Payroll\Models\EmployeeRecord::where('employer_id', $employer->id)
                ->where('is_active', true)->count(),
            'monthly_limit' => $employer->monthly_payroll_limit,
            'monthly_used' => $employer->used_monthly_payroll,
            'monthly_remaining' => $employer->monthly_payroll_limit - $employer->used_monthly_payroll,
        ];

        return $this->respond(['employer' => $employer, 'stats' => $stats, 'recent_batches' => $batches]);
    }

    public function mySalary(Request $request): JsonResponse
    {
        $phone = $request->user()->phone;

        $disbursements = \Modules\Payroll\Models\PayrollDisbursement::where('employee_phone', $phone)
            ->with('batch')
            ->orderByDesc('created_at')
            ->get();

        return $this->respond($disbursements);
    }

    public function downloadCertificate(string $batchId, string $employeePhone): \Illuminate\Http\Response
    {
        return $this->certificateService->generate($batchId, $employeePhone);
    }
}
