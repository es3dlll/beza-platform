<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use App\Modules\Identity\Models\User as IdentityUser;
use App\Modules\Ledger\Models\LedgerAccount;
use App\Modules\Ledger\Models\ReconciliationDiscrepancy;
use App\Modules\Ledger\Models\ReconciliationReport;
use App\Modules\Ledger\Services\LedgerHealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    private LedgerHealthCheck $healthCheck;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthCheck = app(LedgerHealthCheck::class);

        $user = IdentityUser::factory()->create();
        $this->actingAs($user);
    }

    public function test_health_check_returns_expected_structure(): void
    {
        LedgerAccount::factory()->count(3)->create();

        $result = $this->healthCheck->check();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('service', $result);
        $this->assertEquals('ledger', $result['service']);
    }

    public function test_health_check_healthy_when_no_issues(): void
    {
        $result = $this->healthCheck->check();

        $this->assertEquals('healthy', $result['status']);
        $this->assertEquals(0, $result['summary']['open_discrepancies']);
        $this->assertEquals(0, $result['summary']['critical_discrepancies']);
    }

    public function test_health_check_returns_warning_with_open_discrepancies(): void
    {
        $account = LedgerAccount::factory()->create();
        $report = ReconciliationReport::create([
            'id' => Str::ulid()->toBase32(),
            'report_type' => 'reconciliation',
            'status' => 'completed',
            'reporting_date' => now(),
            'total_discrepancies_found' => 1,
            'is_balanced' => false,
            'currency' => 'SYP',
        ]);

        ReconciliationDiscrepancy::create([
            'id' => Str::ulid()->toBase32(),
            'report_id' => $report->id,
            'account_id' => $account->id,
            'discrepancy_type' => 'balance_mismatch',
            'severity' => 'medium',
            'expected_balance' => 1000,
            'actual_balance' => 500,
            'difference' => 500,
            'currency' => 'SYP',
            'resolution_status' => 'open',
        ]);

        $result = $this->healthCheck->check();

        $this->assertEquals(1, $result['summary']['open_discrepancies']);
        $this->assertEquals('warning', $result['checks']['open_discrepancies']['status']);
    }

    public function test_health_check_critical_with_critical_discrepancies(): void
    {
        $account = LedgerAccount::factory()->create();
        $report = ReconciliationReport::create([
            'id' => Str::ulid()->toBase32(),
            'report_type' => 'reconciliation',
            'status' => 'completed',
            'reporting_date' => now(),
            'total_discrepancies_found' => 1,
            'is_balanced' => false,
            'currency' => 'SYP',
        ]);

        ReconciliationDiscrepancy::create([
            'id' => Str::ulid()->toBase32(),
            'report_id' => $report->id,
            'account_id' => $account->id,
            'discrepancy_type' => 'balance_mismatch',
            'severity' => 'critical',
            'expected_balance' => 100_000_000_00,
            'actual_balance' => 0,
            'difference' => 100_000_000_00,
            'currency' => 'SYP',
            'resolution_status' => 'open',
        ]);

        $result = $this->healthCheck->check();

        $this->assertEquals(1, $result['summary']['critical_discrepancies']);
        $this->assertEquals('critical', $result['checks']['open_discrepancies']['status']);
    }

    public function test_health_check_has_all_check_keys(): void
    {
        $result = $this->healthCheck->check();

        $this->assertArrayHasKey('chain_integrity', $result['checks']);
        $this->assertArrayHasKey('last_reconciliation', $result['checks']);
        $this->assertArrayHasKey('open_discrepancies', $result['checks']);
        $this->assertArrayHasKey('cbs_reporting', $result['checks']);
        $this->assertArrayHasKey('imbalanced_accounts', $result['checks']);
    }

    public function test_is_healthy_returns_bool(): void
    {
        $this->assertIsBool($this->healthCheck->isHealthy());
    }

    public function test_get_last_reconciliation_returns_null_when_none(): void
    {
        $this->assertNull($this->healthCheck->getLastReconciliation());
    }

    public function test_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/v1/ledger/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status', 'timestamp', 'service', 'checks', 'summary',
        ]);
    }

    public function test_health_endpoint_shows_healthy_initially(): void
    {
        $response = $this->getJson('/v1/ledger/health');

        $response->assertOk();
        $response->assertJson(['status' => 'healthy']);
    }
}
