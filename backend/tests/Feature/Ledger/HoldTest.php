<?php

declare(strict_types=1);

namespace Tests\Feature\Ledger;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ledger\Models\LedgerAccount;
use Tests\TestCase;

final class HoldTest extends TestCase
{
    use RefreshDatabase;

    private string $accountId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateUser();

        $this->accountId = LedgerAccount::create([
            'id' => '01AR123456789012345678h1',
            'account_number' => '1000-HOLD',
            'name' => 'Hold Test Account',
            'type' => 'asset',
            'currency' => 'SYP',
            'balance' => 100000,
            'available_balance' => 100000,
        ])->id;
    }

    public function test_can_place_hold(): void
    {
        $response = $this->postJson('/api/v1/ledger/holds', [
            'account_id' => $this->accountId,
            'amount' => 30000,
            'currency' => 'SYP',
            'reason' => 'test hold',
            'reference_type' => 'order',
            'reference_id' => 'ord-001',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_hold_reduces_available_balance(): void
    {
        $this->postJson('/api/v1/ledger/holds', [
            'account_id' => $this->accountId,
            'amount' => 30000,
            'currency' => 'SYP',
            'reason' => 'reduce balance test',
            'reference_type' => 'order',
            'reference_id' => 'ord-002',
        ]);

        $response = $this->getJson("/api/v1/ledger/accounts/{$this->accountId}/available");

        $this->assertEquals(70000, $response->json('data.amount'));
    }

    public function test_can_release_hold(): void
    {
        $holdResponse = $this->postJson('/api/v1/ledger/holds', [
            'account_id' => $this->accountId,
            'amount' => 30000,
            'currency' => 'SYP',
            'reason' => 'release test',
            'reference_type' => 'order',
            'reference_id' => 'ord-003',
        ]);

        $holdId = $holdResponse->json('data.id');
        $this->assertNotNull($holdId);

        $this->postJson("/api/v1/ledger/holds/{$holdId}/release", [
            'reason' => 'completed',
        ]);

        $availableResponse = $this->getJson("/api/v1/ledger/accounts/{$this->accountId}/available");
        $this->assertEquals(100000, $availableResponse->json('data.amount'));
    }
}
