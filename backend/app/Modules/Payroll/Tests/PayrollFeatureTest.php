<?php

declare(strict_types=1);

namespace Modules\Payroll\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payroll\DTOs\RegisterEmployerDto;
use Modules\Payroll\DTOs\CreatePayrollBatchDto;
use Modules\Payroll\Enums\EmployerStatus;
use Modules\Payroll\Enums\PayrollBatchStatus;
use Modules\Payroll\Enums\DisbursementStatus;
use Modules\Payroll\Exceptions\EmployerNotFoundException;
use Modules\Payroll\Exceptions\EmployerSuspendedException;
use Modules\Payroll\Exceptions\PayrollBatchNotFoundException;
use Modules\Payroll\Exceptions\PayrollValidationException;
use Modules\Payroll\Models\Employer;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollDisbursement;
use Modules\Payroll\Models\EmployeeRecord;
use Modules\Payroll\Services\PayrollService;
use Modules\Payroll\Services\CsvParserService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class PayrollFeatureTest extends TestCase
{
    use RefreshDatabase;

    private PayrollService $payrollService;
    private CsvParserService $csvParser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payrollService = $this->app->make(PayrollService::class);
        $this->csvParser = $this->app->make(CsvParserService::class);
    }

    public function test_can_register_employer(): void
    {
        $user = $this->createUser('01ARpayrollUser001');

        $employer = $this->payrollService->registerEmployer(new RegisterEmployerDto(
            userId: $user->id,
            companyName: 'Al-Sham Tech',
            companyNameAr: 'شام تك',
            phone: '963111111111',
            governorate: 'Damascus',
            city: 'Al-Mazzah',
        ));

        $this->assertInstanceOf(Employer::class, $employer);
        $this->assertEquals(EmployerStatus::PENDING->value, $employer->status);
        $this->assertEquals('Al-Sham Tech', $employer->company_name);
    }

    public function test_can_approve_employer(): void
    {
        $employer = $this->seedEmployer('01ARpayrollUser002');

        $approved = $this->payrollService->approveEmployer($employer->id, 'admin-001');

        $this->assertEquals(EmployerStatus::ACTIVE->value, $approved->status);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_can_suspend_employer(): void
    {
        $employer = $this->seedEmployer('01ARpayrollUser003');
        $this->payrollService->approveEmployer($employer->id, 'admin-001');

        $suspended = $this->payrollService->suspendEmployer($employer->id);

        $this->assertEquals(EmployerStatus::SUSPENDED->value, $suspended->status);
    }

    public function test_throws_on_missing_employer(): void
    {
        $this->expectException(EmployerNotFoundException::class);
        $this->payrollService->findEmployerOrFail('nonexistent');
    }

    public function test_can_create_batch_with_employees(): void
    {
        $employer = $this->seedActiveEmployer('01ARpayrollUser004');

        $batch = $this->payrollService->createBatch(new CreatePayrollBatchDto(
            employerId: $employer->id,
            periodMonth: '2026-05',
            employees: [
                ['employee_name' => 'Ahmed Ali', 'phone' => '963911111111', 'amount' => 500000],
                ['employee_name' => 'Sara Hassan', 'phone' => '963922222222', 'amount' => 750000],
            ],
        ));

        $this->assertInstanceOf(PayrollBatch::class, $batch);
        $this->assertEquals(2, $batch->total_employees);
        $this->assertEquals(1250000, $batch->total_amount);
        $this->assertEquals(PayrollBatchStatus::PENDING->value, $batch->status);

        $this->assertEquals(2, PayrollDisbursement::count());
        $this->assertEquals(2, EmployeeRecord::count());
    }

    public function test_can_create_batch_from_csv(): void
    {
        $employer = $this->seedActiveEmployer('01ARpayrollUser005');

        $csv = "employee_name,phone,amount\nKhalid Othman,963933333333,600000\nNour Said,963944444444,450000";

        $batch = $this->payrollService->createBatchFromCsv(
            $employer->id,
            $csv,
            '2026-05',
        );

        $this->assertEquals(2, $batch->total_employees);
        $this->assertEquals(1050000, $batch->total_amount);
    }

    public function test_csv_parser_validates_headers(): void
    {
        $this->expectException(PayrollValidationException::class);
        $this->expectExceptionMessage('Missing required CSV column: phone');

        $this->csvParser->parse("employee_name,amount\nTest,1000");
    }

    public function test_csv_parser_validates_row(): void
    {
        $this->expectException(PayrollValidationException::class);

        $this->csvParser->parse("employee_name,phone,amount\nTest,9639,abc");
    }

    public function test_can_approve_and_process_batch(): void
    {
        $employer = $this->seedActiveEmployer('01ARpayrollUser006');

        $batch = $this->payrollService->createBatch(new CreatePayrollBatchDto(
            employerId: $employer->id,
            periodMonth: '2026-05',
            employees: [
                ['employee_name' => 'Ali', 'phone' => '963911111111', 'amount' => 300000],
            ],
        ));

        $approved = $this->payrollService->approveBatch($batch->id, 'admin-001');
        $this->assertEquals(PayrollBatchStatus::APPROVED->value, $approved->status);

        $processed = $this->payrollService->processBatch($batch->id);
        $this->assertEquals(PayrollBatchStatus::COMPLETED->value, $processed->status);

        $disbursements = PayrollDisbursement::where('payroll_batch_id', $batch->id)->get();
        $this->assertEquals(DisbursementStatus::COMPLETED->value, $disbursements->first()->status);
        $this->assertNotNull($disbursements->first()->wallet_transaction_id);
    }

    public function test_throws_when_approving_unprocessed_employer(): void
    {
        $employer = $this->seedEmployer('01ARpayrollUser007');

        $this->expectException(EmployerSuspendedException::class);

        $this->payrollService->createBatch(new CreatePayrollBatchDto(
            employerId: $employer->id,
            periodMonth: '2026-05',
            employees: [
                ['employee_name' => 'Test', 'phone' => '963900000000', 'amount' => 100000],
            ],
        ));
    }

    public function test_throws_when_batch_not_approved(): void
    {
        $employer = $this->seedActiveEmployer('01ARpayrollUser008');
        $batch = $this->payrollService->createBatch(new CreatePayrollBatchDto(
            employerId: $employer->id,
            periodMonth: '2026-05',
            employees: [
                ['employee_name' => 'Test', 'phone' => '963900000000', 'amount' => 100000],
            ],
        ));

        $this->expectException(PayrollValidationException::class);
        $this->payrollService->processBatch($batch->id);
    }

    public function test_uploads_csv_and_creates_disbursements(): void
    {
        $employer = $this->seedActiveEmployer('01ARpayrollUser009');
        $csv = "employee_name,phone,amount\nFadi,963955555555,200000\nLina,963966666666,350000\nOmar,963977777777,500000";

        $batch = $this->payrollService->createBatchFromCsv($employer->id, $csv, '2026-05');

        $this->assertEquals(3, $batch->total_employees);
        $this->assertEquals(1050000, $batch->total_amount);

        $disbursements = PayrollDisbursement::where('payroll_batch_id', $batch->id)->get();
        $this->assertCount(3, $disbursements);
    }

    /* ──── Helpers ──── */

    private function createUser(string $id, string $phone = '963900000000'): User
    {
        $user = new User();
        $user->id = $id;
        $user->phone = $phone;
        $user->status = 'active';
        $user->save();
        return $user;
    }

    private function seedEmployer(string $userId): Employer
    {
        $this->createUser($userId, $userId);
        return $this->payrollService->registerEmployer(new RegisterEmployerDto(
            userId: $userId,
            companyName: 'Test Co',
            companyNameAr: 'شركة اختبار',
            phone: $userId,
            governorate: 'Damascus',
            city: 'Center',
        ));
    }

    private function seedActiveEmployer(string $userId): Employer
    {
        $employer = $this->seedEmployer($userId);
        return $this->payrollService->approveEmployer($employer->id, 'admin-test');
    }
}
