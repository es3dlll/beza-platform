<?php

declare(strict_types=1);

namespace Modules\Admin\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticateAdmin();
        $this->token = $this->authToken;
    }

    public function test_financing_admin_dashboard(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/financing/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_loans_disbursed', 'active_loans', 'npl_ratio', 'portfolio_by_product', 'loans_by_status'],
            ]);
    }

    public function test_education_admin_dashboard(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/education/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_institutions', 'total_students', 'total_fees_collected', 'overdue_by_institution'],
            ]);
    }

    public function test_humanitarian_admin_dashboard(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/humanitarian/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['active_programs', 'total_beneficiaries', 'budget_utilization_percent', 'disbursement_sla_seconds'],
            ]);
    }

    public function test_open_finance_admin_dashboard(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/open-finance/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_developer_apps', 'active_api_keys', 'total_api_calls_today', 'webhook_failures_today'],
            ]);
    }

    public function test_marketplace_admin_dashboard(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/marketplace/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_gmv', 'total_orders', 'active_vendors', 'top_categories_by_gmv', 'fulfillment_rate'],
            ]);
    }

    public function test_escrow_admin_dashboard(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/escrow/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_escrows', 'held_amount', 'pending_disputes', 'avg_resolution_time_hours'],
            ]);
    }

    public function test_takaful_admin_dashboard(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/takaful/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_policies', 'active_policies', 'total_claims', 'approved_claims', 'loss_ratio'],
            ]);
    }

    public function test_investments_admin_dashboard(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/v1/admin/investments/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total_aum', 'total_subscribers', 'funds_count', 'total_subscriptions_volume'],
            ]);
    }
}
