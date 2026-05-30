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

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
