<?php

declare(strict_types=1);

namespace Modules\Investments\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Modules\Investments\Enums\SubscriptionStatus;
use Modules\Investments\Models\InvestmentFund;
use Modules\Investments\Models\InvestmentSubscription;
use Modules\Investments\Models\InvestmentNav;
use Modules\Investments\Services\InvestmentService;
use Tests\TestCase;

final class InvestmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private InvestmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(InvestmentService::class);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->id = \Illuminate\Support\Str::ulid()->toBase32();
        $user->phone = '+963' . substr($user->id, -9);
        $user->status = 'active';
        $user->phone_verified_at = now();
        $user->save();
        return $user;
    }

    private function createFund(array $overrides = []): InvestmentFund
    {
        return InvestmentFund::create(array_merge([
            'name' => 'Beza Growth Fund',
            'name_ar' => 'صندوق بيزا للنمو',
            'type' => 'equity',
            'min_investment' => 100000,
            'current_nav' => 100000,
            'is_active' => true,
        ], $overrides));
    }

    public function test_can_list_funds(): void
    {
        $this->createFund(['name' => 'Fund A']);
        $this->createFund(['name' => 'Fund B']);

        $funds = $this->service->listFunds();

        $this->assertCount(2, $funds);
    }

    public function test_can_subscribe(): void
    {
        $user = $this->createUser();
        $fund = $this->createFund(['current_nav' => 100000]);

        $subscription = $this->service->subscribe($user->id, $fund->id, 500000);

        $this->assertEquals(SubscriptionStatus::SETTLED->value, $subscription->status);
        $this->assertEquals(500000, $subscription->total_amount);
        $this->assertEquals(500000, $subscription->units);
        $this->assertNotNull($subscription->settled_at);
        $this->assertEquals('subscribe', $subscription->type);
    }

    public function test_can_redeem(): void
    {
        $user = $this->createUser();
        $fund = $this->createFund(['current_nav' => 100000]);

        $subscription = $this->service->redeem($user->id, $fund->id, 500);

        $this->assertEquals(SubscriptionStatus::REDEEMED->value, $subscription->status);
        $this->assertEquals(500, $subscription->units);
        $this->assertEquals('redeem', $subscription->type);
        $this->assertNotNull($subscription->settled_at);
    }

    public function test_can_list_subscriptions(): void
    {
        $user = $this->createUser();
        $fund = $this->createFund();

        $this->service->subscribe($user->id, $fund->id, 200000);
        $this->service->redeem($user->id, $fund->id, 100);

        $subscriptions = $this->service->listSubscriptions($user->id);

        $this->assertCount(2, $subscriptions);
    }

    public function test_can_record_nav(): void
    {
        $fund = $this->createFund(['current_nav' => 100000]);

        $nav = $this->service->updateNav($fund->id, 105000);

        $this->assertEquals(105000, $nav->nav);
        $this->assertEquals($fund->id, $nav->fund_id);

        $fund->refresh();
        $this->assertEquals(105000, $fund->current_nav);
    }

    public function test_can_calculate_zakat(): void
    {
        $zakat = $this->service->calculateZakat(1000000);

        $this->assertEquals(25000, $zakat);
    }
}
