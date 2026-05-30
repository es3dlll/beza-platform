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

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
