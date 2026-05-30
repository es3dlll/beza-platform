<?php

declare(strict_types=1);

namespace Modules\Takaful\Tests;

use Modules\Identity\Models\User;
use Modules\Takaful\Models\TakafulClaim;
use Modules\Takaful\Models\TakafulPolicy;
use Modules\Takaful\Models\TakafulProduct;
use Modules\Takaful\Services\TakafulService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TakafulFeatureTest extends TestCase
{
    use RefreshDatabase;

    private TakafulService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(TakafulService::class);
        $this->user = User::factory()->create([
            'id' => '01AR12345678901234567890',
            'phone' => '+963123456789',
            'status' => 'active',
            'phone_verified_at' => now(),
        ]);
    }

    public function test_can_list_products(): void
    {
        TakafulProduct::create([
            'name' => 'Family Takaful',
            'name_ar' => 'التكافل العائلي',
            'type' => 'family',
            'min_premium' => 1000,
            'max_premium' => 100000,
            'coverage_amount' => 500000,
        ]);

        $products = $this->service->listProducts();

        $this->assertCount(1, $products);
        $this->assertEquals('Family Takaful', $products->first()->name);
    }

    public function test_can_subscribe(): void
    {
        $product = TakafulProduct::create([
            'name' => 'Car Takaful',
            'name_ar' => 'تكافل السيارات',
            'type' => 'car',
            'min_premium' => 5000,
            'max_premium' => 500000,
            'coverage_amount' => 2000000,
        ]);

        $policy = $this->service->subscribe(
            $this->user->id,
            $product->id,
            25000,
            1000000,
            '2025-08-01',
            '2026-07-31',
        );

        $this->assertInstanceOf(TakafulPolicy::class, $policy);
        $this->assertEquals($this->user->id, $policy->user_id);
        $this->assertEquals($product->id, $policy->product_id);
        $this->assertEquals(25000, $policy->premium);
        $this->assertEquals('active', $policy->status);
    }

    public function test_can_list_policies(): void
    {
        $product = TakafulProduct::create([
            'name' => 'Health Takaful',
            'name_ar' => 'التكافل الصحي',
            'type' => 'health',
            'min_premium' => 2000,
            'max_premium' => 200000,
            'coverage_amount' => 1000000,
        ]);

        $this->service->subscribe(
            $this->user->id, $product->id, 15000, 500000, '2025-09-01', '2026-08-31',
        );

        $policies = $this->service->listPolicies($this->user->id);

        $this->assertCount(1, $policies);
        $this->assertEquals($product->id, $policies->first()->product_id);
    }

    public function test_can_file_claim(): void
    {
        $product = TakafulProduct::create([
            'name' => 'Property Takaful',
            'name_ar' => 'التكافل العقاري',
            'type' => 'property',
            'min_premium' => 3000,
            'max_premium' => 300000,
            'coverage_amount' => 1500000,
        ]);

        $policy = $this->service->subscribe(
            $this->user->id, $product->id, 30000, 1500000, '2025-10-01', '2026-09-30',
        );

        $claim = $this->service->fileClaim($policy->id, 200000, 'Property damage due to fire');

        $this->assertInstanceOf(TakafulClaim::class, $claim);
        $this->assertEquals($policy->id, $claim->policy_id);
        $this->assertEquals(200000, $claim->amount);
        $this->assertEquals('filed', $claim->status);
    }

    public function test_can_approve_claim(): void
    {
        $product = TakafulProduct::create([
            'name' => 'Life Takaful',
            'name_ar' => 'التكافل على الحياة',
            'type' => 'life',
            'min_premium' => 1000,
            'max_premium' => 100000,
            'coverage_amount' => 2000000,
        ]);

        $policy = $this->service->subscribe(
            $this->user->id, $product->id, 50000, 2000000, '2025-11-01', '2026-10-31',
        );

        $claim = $this->service->fileClaim($policy->id, 500000, 'Critical illness');

        $approved = $this->service->approveClaim($claim->id, 450000);

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals(450000, $approved->approved_amount);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_admin_dashboard(): void
    {
        $product = TakafulProduct::create([
            'name' => 'General Takaful',
            'name_ar' => 'التكافل العام',
            'type' => 'general',
            'min_premium' => 1000,
            'max_premium' => 100000,
            'coverage_amount' => 1000000,
        ]);

        $policy = $this->service->subscribe(
            $this->user->id, $product->id, 10000, 500000, '2025-12-01', '2026-11-30',
        );

        $claim = $this->service->fileClaim($policy->id, 100000, 'Accident');
        $this->service->approveClaim($claim->id, 80000);

        $dashboard = $this->service->adminDashboard();

        $this->assertEquals(1, $dashboard['total_policies']);
        $this->assertEquals(1, $dashboard['active_policies']);
        $this->assertEquals(1, $dashboard['total_claims']);
        $this->assertEquals(1, $dashboard['approved_claims']);
        $this->assertEquals(800.0, $dashboard['loss_ratio']);
    }
}
