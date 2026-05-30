<?php

declare(strict_types=1);

namespace Modules\GovCollections\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\GovCollections\Models\GovServiceProvider;
use Modules\GovCollections\Models\GovCollection;
use Modules\GovCollections\Services\GovCollectionService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class GovCollectionFeatureTest extends TestCase
{
    use RefreshDatabase;
    private GovCollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(GovCollectionService::class);
    }

    public function test_can_inquire(): void
    {
        $user = $this->createUser('01ARgovUser001');
        $provider = GovServiceProvider::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'CBS Taxes','name_ar'=>'الضرائب','code'=>'CBS_TAX','type'=>'tax','is_active'=>true]);
        $inquiry = $this->service->inquire($user->id, $provider->id, 'tax_income', '123456');
        $this->assertNotNull($inquiry->amount_due);
    }

    public function test_can_pay(): void
    {
        $user = $this->createUser('01ARgovUser002');
        $provider = GovServiceProvider::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'BSO','name_ar'=>'المصرف العقاري','code'=>'BSO','type'=>'bank','is_active'=>true]);
        $inquiry = $this->service->inquire($user->id, $provider->id, 'loan', '654321');
        $collection = $this->service->pay($user->id, $inquiry->id);
        $this->assertEquals('paid', $collection->status);
        $this->assertNotNull($collection->receipt_number);
    }

    public function test_lists_providers(): void
    {
        GovServiceProvider::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Test','name_ar'=>'اختبار','code'=>'TST','type'=>'test','is_active'=>true]);
        $this->assertCount(1, $this->service->listProviders());
    }

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
