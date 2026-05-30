<?php

declare(strict_types=1);

namespace Modules\Education\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Education\Models\EducationInstitution;
use Modules\Education\Models\EducationStudent;
use Modules\Education\Models\EducationFee;
use Modules\Education\Services\EducationService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class EducationFeatureTest extends TestCase
{
    use RefreshDatabase;
    private EducationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(EducationService::class);
    }

    public function test_list_institutions(): void
    {
        EducationInstitution::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Damascus University','name_ar'=>'جامعة دمشق','code'=>'DU','type'=>'university','is_active'=>true]);
        $this->assertCount(1, $this->service->listInstitutions());
    }

    public function test_register_student(): void
    {
        $user = $this->createUser('01AReduU01');
        $inst = EducationInstitution::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'School','name_ar'=>'مدرسة','code'=>'SCH','type'=>'school','is_active'=>true]);
        $student = $this->service->registerStudent($user->id, $inst->id, 'STU001', 'Ali', 'علي');
        $this->assertEquals('active', $student->status);
    }

    public function test_create_and_pay_fee(): void
    {
        $user = $this->createUser('01AReduU02');
        $inst = EducationInstitution::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'SCH2','name_ar'=>'م2','code'=>'SCH2','type'=>'school','is_active'=>true]);
        $student = $this->service->registerStudent($user->id, $inst->id, 'STU002', 'Omar', 'عمر');
        $fee = $this->service->createFee($student->id, 'tuition', 50000, '2026-09-01');
        $this->assertEquals('pending', $fee->status);
        $paid = $this->service->payFee($fee->id, 50000);
        $this->assertEquals('paid', $paid->status);
        $this->assertNotNull($paid->receipt_number);
    }

    public function test_institution_dashboard_returns_stats(): void
    {
        $user = $this->createUser('01AReduU03');
        $inst = EducationInstitution::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Test Inst','name_ar'=>'مؤسسة','code'=>'TI','type'=>'school','is_active'=>true]);
        $student = $this->service->registerStudent($user->id, $inst->id, 'STU003', 'Test', 'اختبار');
        $this->service->createFee($student->id, 'tuition', 50000, '2025-01-01');
        $fee2 = $this->service->createFee($student->id, 'books', 30000, '2026-12-01');
        $this->service->payFee($fee2->id, 30000);
        $dashboard = $this->service->institutionDashboard($inst->id);
        $this->assertArrayHasKey('collected', $dashboard);
        $this->assertArrayHasKey('pending', $dashboard);
        $this->assertArrayHasKey('overdue', $dashboard);
        $this->assertArrayHasKey('totalAmount', $dashboard);
        $this->assertArrayHasKey('collectionRate', $dashboard);
        $this->assertArrayHasKey('recent', $dashboard);
        $this->assertEquals(30000, $dashboard['collected']);
        $this->assertEquals(1, $dashboard['pending']);
        $this->assertEquals(1, $dashboard['overdue']);
    }

    public function test_bulk_create_fees(): void
    {
        $user = $this->createUser('01AReduU04');
        $inst = EducationInstitution::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Bulk Inst','name_ar'=>'مجمع','code'=>'BI','type'=>'school','is_active'=>true]);
        $student = $this->service->registerStudent($user->id, $inst->id, 'STU004', 'Bulk', 'مجموع');
        $result = $this->service->bulkCreateFees($inst->id, [
            ['student_id' => $student->id, 'fee_type' => 'tuition', 'amount' => 50000, 'due_date' => '2026-09-01'],
            ['student_id' => $student->id, 'fee_type' => 'books', 'amount' => 20000, 'due_date' => '2026-10-01'],
            ['student_id' => 'invalid', 'fee_type' => 'other', 'amount' => 10000, 'due_date' => '2026-11-01'],
        ]);
        $this->assertEquals(2, $result['created']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_generate_receipt(): void
    {
        $user = $this->createUser('01AReduU05');
        $inst = EducationInstitution::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Receipt Inst','name_ar'=>'إيصال','code'=>'RI','type'=>'school','is_active'=>true]);
        $student = $this->service->registerStudent($user->id, $inst->id, 'STU005', 'Receipt', 'إيصال');
        $fee = $this->service->createFee($student->id, 'tuition', 60000, '2026-09-01');
        $this->service->payFee($fee->id, 60000);
        $receipt = $this->service->generateReceipt($fee->fresh()->id);
        $this->assertArrayHasKey('receipt_number', $receipt);
        $this->assertArrayHasKey('student_name', $receipt);
        $this->assertArrayHasKey('amount', $receipt);
        $this->assertArrayHasKey('date', $receipt);
        $this->assertArrayHasKey('institution_code', $receipt);
        $this->assertEquals('Receipt', $receipt['student_name']);
        $this->assertEquals('RI', $receipt['institution_code']);
    }

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
