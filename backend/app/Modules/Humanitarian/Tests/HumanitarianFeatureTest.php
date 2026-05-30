<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Humanitarian\Models\HumanitarianOrganization;
use Modules\Humanitarian\Models\HumanitarianProgram;
use Modules\Humanitarian\Services\HumanitarianService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class HumanitarianFeatureTest extends TestCase
{
    use RefreshDatabase;
    private HumanitarianService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(HumanitarianService::class);
    }

    public function test_list_organizations(): void
    {
        HumanitarianOrganization::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'UNHCR','name_ar'=>'المفوضية','code'=>'UNHCR','type'=>'un','is_active'=>true]);
        $this->assertCount(1, $this->service->listOrganizations());
    }

    public function test_list_programs(): void
    {
        $org = HumanitarianOrganization::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'UNICEF','name_ar'=>'اليونيسف','code'=>'UNICEF','type'=>'un','is_active'=>true]);
        HumanitarianProgram::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'organization_id'=>$org->id,'name'=>'Education Aid','name_ar'=>'مساعدات تعليمية','type'=>'education','total_budget'=>10000000,'remaining_budget'=>8000000,'status'=>'active']);
        $this->assertCount(1, $this->service->listPrograms());
    }

    public function test_create_disbursement(): void
    {
        $user = $this->createUser('01ARhumU01');
        $org = HumanitarianOrganization::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'WFP','name_ar'=>'برنامج الغذاء','code'=>'WFP','type'=>'un','is_active'=>true]);
        $program = HumanitarianProgram::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'organization_id'=>$org->id,'name'=>'Food Aid','name_ar'=>'مساعدات غذائية','type'=>'food','total_budget'=>5000000,'remaining_budget'=>5000000,'status'=>'active']);
        $disbursement = $this->service->createDisbursement($program->id, $user->id, 'food', 100000);
        $this->assertEquals('disbursed', $disbursement->status);
        $this->assertNotNull($disbursement->reference_number);
    }

    public function test_batch_disburse(): void
    {
        $user1 = $this->createUser('01ARhumU01');
        $user2 = $this->createUser('01ARhumU02');
        $org = HumanitarianOrganization::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'WFP','name_ar'=>'برنامج الغذاء','code'=>'WFP','type'=>'un','is_active'=>true]);
        $program = HumanitarianProgram::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'organization_id'=>$org->id,'name'=>'Food Aid','name_ar'=>'مساعدات غذائية','type'=>'food','total_budget'=>5000000,'remaining_budget'=>5000000,'status'=>'active']);
        $beneficiaries = [
            ['user_id' => $user1->id, 'amount' => 100000, 'type' => 'cash'],
            ['user_id' => $user2->id, 'amount' => 200000, 'type' => 'voucher'],
        ];
        $result = $this->service->batchDisburse($program->id, $beneficiaries);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['succeeded']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
    }

    public function test_ngo_dashboard(): void
    {
        $user = $this->createUser('01ARhumU01');
        $org = HumanitarianOrganization::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'UNICEF','name_ar'=>'اليونيسف','code'=>'UNICEF','type'=>'un','is_active'=>true]);
        $program = HumanitarianProgram::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'organization_id'=>$org->id,'name'=>'Education Aid','name_ar'=>'مساعدات تعليمية','type'=>'education','total_budget'=>10000000,'remaining_budget'=>10000000,'status'=>'active']);
        $this->service->createDisbursement($program->id, $user->id, 'cash', 2000000, 'BEN-001');
        $dashboard = $this->service->ngoDashboard($org->id);
        $this->assertEquals(1, $dashboard['total_programs']);
        $this->assertEquals(10000000, $dashboard['total_budget']);
        $this->assertEquals(8000000, $dashboard['remaining_budget']);
        $this->assertEquals(2000000, $dashboard['disbursed_amount']);
        $this->assertEquals(1, $dashboard['active_beneficiaries']);
        $this->assertCount(1, $dashboard['recent_disbursements']);
    }

    public function test_agent_pickup_code(): void
    {
        $user = $this->createUser('01ARhumU01');
        $org = HumanitarianOrganization::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'WFP','name_ar'=>'برنامج الغذاء','code'=>'WFP','type'=>'un','is_active'=>true]);
        $program = HumanitarianProgram::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'organization_id'=>$org->id,'name'=>'Food Aid','name_ar'=>'مساعدات غذائية','type'=>'food','total_budget'=>5000000,'remaining_budget'=>5000000,'status'=>'active']);
        $disbursement = $this->service->createDisbursement($program->id, $user->id, 'food', 100000);
        $code = $this->service->agentPickupCode($disbursement->id);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $disbursement->refresh();
        $this->assertEquals($code, $disbursement->pickup_code);
    }

    public function test_ofac_screen(): void
    {
        $result = $this->service->ofacScreen(['SAN123', 'NORMAL', 'SAN456']);
        $this->assertEquals(3, $result['screened']);
        $this->assertCount(2, $result['flagged']);
        $this->assertContains('SAN123', $result['flagged']);
        $this->assertContains('SAN456', $result['flagged']);
    }

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
