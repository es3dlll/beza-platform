<?php

declare(strict_types=1);

namespace Modules\Financing\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Financing\Models\LoanProduct;
use Modules\Financing\Models\Loan;
use Modules\Financing\Services\FinancingService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class FinancingFeatureTest extends TestCase
{
    use RefreshDatabase;
    private FinancingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(FinancingService::class);
    }

    public function test_can_list_products(): void
    {
        LoanProduct::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Personal Loan','name_ar'=>'قرض شخصي','min_amount'=>50000,'max_amount'=>5000000,'interest_rate'=>12.5,'min_term_days'=>30,'max_term_days'=>365,'is_active'=>true]);
        $this->assertCount(1, $this->service->listProducts());
    }

    public function test_can_apply_for_loan(): void
    {
        $user = $this->createUser('01ARfinUser01');
        $product = LoanProduct::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Micro Loan','name_ar'=>'قرض صغير','min_amount'=>10000,'max_amount'=>500000,'interest_rate'=>10,'min_term_days'=>30,'max_term_days'=>180,'is_active'=>true]);
        $loan = $this->service->apply($user->id, $product->id, 100000, 90, 'Business');
        $this->assertEquals('pending', $loan->status);
        $this->assertEquals(100000, $loan->principal);
        $this->assertNotNull($loan->total_repayable);
    }

    public function test_can_approve_and_disburse(): void
    {
        $user = $this->createUser('01ARfinUser02');
        $product = LoanProduct::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Test','name_ar'=>'اختبار','min_amount'=>1000,'max_amount'=>100000,'interest_rate'=>5,'min_term_days'=>7,'max_term_days'=>30,'is_active'=>true]);
        $loan = $this->service->apply($user->id, $product->id, 50000, 30);
        $this->service->approve($loan->id);
        $this->service->disburse($loan->id);
        $this->assertEquals('disbursed', Loan::find($loan->id)->status);
    }

    public function test_can_repay(): void
    {
        $user = $this->createUser('01ARfinUser03');
        $product = LoanProduct::create(['id'=>(string)\Illuminate\Support\Str::ulid(),'name'=>'Quick','name_ar'=>'سريع','min_amount'=>1000,'max_amount'=>50000,'interest_rate'=>10,'min_term_days'=>7,'max_term_days'=>60,'is_active'=>true]);
        $loan = $this->service->apply($user->id, $product->id, 10000, 30);
        $this->service->approve($loan->id);
        $this->service->disburse($loan->id);
        $installment = $this->service->repay($loan->id, 5000);
        $this->assertNotNull($installment->paid_amount);
    }

    private function createUser(string $id): User
    {
        $user = new User(); $user->id = $id; $user->phone = $id; $user->status = 'active'; $user->save();
        return $user;
    }
}
