<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentCommission;
use App\Modules\Agent\Models\AgentSettlement;
use App\Modules\Agent\Models\FraudAlert;
use Database\Factories\AgentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentOversightTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $token;

    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@beza.test',
        ]);

        $this->token = $this->admin->createToken('admin-token', ['admin'])->plainTextToken;

        $this->agent = AgentFactory::new()->create([
            'region' => 'دمشق',
            'status' => 'active',
        ]);
    }

    private function auth(): self
    {
        return $this->withToken($this->token)->withCookie('admin_token', $this->token);
    }

    private function createCommission(string $status = 'accrued'): AgentCommission
    {
        return AgentCommission::create([
            'agent_id' => $this->agent->id,
            'agent_transaction_id' => null,
            'type' => 'cash_in_fee',
            'amount' => 5000,
            'currency' => 'SYP',
            'rate' => 0.0100,
            'status' => $status,
        ]);
    }

    private function createSettlement(string $status = 'completed'): AgentSettlement
    {
        return AgentSettlement::create([
            'agent_id' => $this->agent->id,
            'period_start' => now()->subDays(7),
            'period_end' => now()->subDay(),
            'total_volume' => 500000,
            'total_commission' => 5000,
            'net_amount' => 5000,
            'currency' => 'SYP',
            'status' => $status,
        ]);
    }

    private function createFraudAlert(string $status = 'open'): FraudAlert
    {
        return FraudAlert::create([
            'agent_id' => $this->agent->id,
            'type' => 'suspicious_flow',
            'severity' => 'high',
            'description' => 'حجم معاملات غير طبيعي',
            'status' => $status,
        ]);
    }

    public function test_index_lists_all_agents(): void
    {
        AgentFactory::new()->count(3)->create();

        $response = $this->auth()->getJson('/api/v1/admin/agents');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta'])
            ->assertJsonCount(4, 'data');
    }

    public function test_index_filters_by_status(): void
    {
        AgentFactory::new()->pending()->create();

        $response = $this->auth()->getJson('/api/v1/admin/agents?status=pending');

        $response->assertOk();
        $this->assertCount(1, $response['data']);
        $this->assertEquals('pending', $response['data'][0]['status']);
    }

    public function test_index_requires_permission(): void
    {
        $restrictedAdmin = User::factory()->create(['role' => 'admin']);
        $adminToken = $restrictedAdmin->createToken('admin-token', ['admin'])->plainTextToken;
        $restrictedAdmin->update(['role' => 'user']);

        $response = $this->withToken($adminToken)
            ->getJson('/api/v1/admin/agents');

        $response->assertForbidden();
    }

    public function test_show_returns_agent_detail(): void
    {
        $response = $this->auth()->getJson("/api/v1/admin/agents/{$this->agent->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->agent->id);
    }

    public function test_show_returns_404_for_missing_agent(): void
    {
        $response = $this->auth()->getJson('/api/v1/admin/agents/99999');

        $response->assertNotFound();
    }

    public function test_commissions_lists_agent_commissions(): void
    {
        $this->createCommission();
        $this->createCommission('settled');

        $response = $this->auth()->getJson("/api/v1/admin/agents/{$this->agent->id}/commissions");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_approve_commission_approves_accrued_commission(): void
    {
        $commission = $this->createCommission('accrued');

        $response = $this->auth()->postJson("/api/v1/admin/commissions/{$commission->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'settled');

        $this->assertDatabaseHas('ledger_entries', [
            'ledgerable_id' => $commission->id,
            'ledgerable_type' => AgentCommission::class,
            'amount' => 5000,
            'direction' => 'credit',
        ]);

        $this->agent->refresh();
        $this->assertEquals(5000, $this->agent->balance);
    }

    public function test_approve_commission_fails_if_already_settled(): void
    {
        $commission = $this->createCommission('settled');

        $response = $this->auth()->postJson("/api/v1/admin/commissions/{$commission->id}/approve");

        $response->assertStatus(422);
    }

    public function test_approve_commission_returns_404_for_missing(): void
    {
        $response = $this->auth()->postJson('/api/v1/admin/commissions/99999/approve');

        $response->assertNotFound();
    }

    public function test_settlements_lists_agent_settlements(): void
    {
        $this->createSettlement('completed');
        $this->createSettlement('pending');

        $response = $this->auth()->getJson("/api/v1/admin/agents/{$this->agent->id}/settlements");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_approve_settlement_approves_pending(): void
    {
        $settlement = $this->createSettlement('pending');

        $response = $this->auth()->postJson("/api/v1/admin/settlements/{$settlement->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('ledger_entries', [
            'ledgerable_id' => $settlement->id,
            'ledgerable_type' => AgentSettlement::class,
            'direction' => 'credit',
        ]);
    }

    public function test_approve_settlement_returns_404_for_missing(): void
    {
        $response = $this->auth()->postJson('/api/v1/admin/settlements/99999/approve');

        $response->assertNotFound();
    }

    public function test_fraud_alerts_lists_all_alerts(): void
    {
        $this->createFraudAlert('open');
        $this->createFraudAlert('investigating');

        $response = $this->auth()->getJson('/api/v1/admin/fraud-alerts');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_resolve_fraud_alert_resolves_open_alert(): void
    {
        $alert = $this->createFraudAlert('open');

        $response = $this->auth()->postJson("/api/v1/admin/fraud-alerts/{$alert->id}/resolve", [
            'action' => 'dismiss',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('fraud_alerts', [
            'id' => $alert->id,
            'resolved_by' => $this->admin->id,
        ]);
    }

    public function test_resolve_fraud_alert_fails_if_already_resolved(): void
    {
        $alert = $this->createFraudAlert('resolved');

        $response = $this->auth()->postJson("/api/v1/admin/fraud-alerts/{$alert->id}/resolve", [
            'action' => 'dismiss',
        ]);

        $response->assertStatus(422);
    }

    public function test_all_endpoints_return_401_without_auth(): void
    {
        $this->getJson('/api/v1/admin/agents')->assertUnauthorized();
        $this->getJson("/api/v1/admin/agents/{$this->agent->id}")->assertUnauthorized();
        $this->getJson("/api/v1/admin/agents/{$this->agent->id}/commissions")->assertUnauthorized();
        $this->getJson("/api/v1/admin/agents/{$this->agent->id}/settlements")->assertUnauthorized();
        $this->postJson('/api/v1/admin/commissions/1/approve')->assertUnauthorized();
        $this->postJson('/api/v1/admin/settlements/1/approve')->assertUnauthorized();
        $this->getJson('/api/v1/admin/fraud-alerts')->assertUnauthorized();
        $this->postJson('/api/v1/admin/fraud-alerts/1/resolve', ['action' => 'dismiss'])->assertUnauthorized();
    }
}
