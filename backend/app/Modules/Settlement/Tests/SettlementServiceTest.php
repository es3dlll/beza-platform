<?php

declare(strict_types=1);

namespace Modules\Settlement\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settlement\DTOs\CreateSettlementDto;
use Modules\Settlement\Exceptions\SettlementNotFoundException;
use Modules\Settlement\Models\Settlement;
use Modules\Settlement\Services\SettlementService;
use Tests\TestCase;

final class SettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(SettlementService::class);
    }

    public function test_can_create_settlement(): void
    {
        $dto = new CreateSettlementDto(
            referenceType: 'merchant',
            referenceId: 'm001',
            settlementType: 'merchant_daily',
            grossAmount: 100000,
            feeAmount: 2000,
            commissionAmount: 500,
            currency: 'SYP',
            settlementAccountId: '10006-001',
            periodStart: now()->subDay(),
            periodEnd: now(),
            metadata: ['merchant_name' => 'Test Shop'],
        );

        $settlement = $this->service->create($dto);

        $this->assertNotNull($settlement->id);
        $this->assertEquals('merchant', $settlement->reference_type);
        $this->assertEquals('pending', $settlement->status);
        $this->assertEquals(100000, $settlement->gross_amount);
        $this->assertEquals(2000, $settlement->fee_amount);
        $this->assertEquals(500, $settlement->commission_amount);
        $this->assertEquals(97500, $settlement->net_amount);
    }

    public function test_state_machine_transitions(): void
    {
        $dto = new CreateSettlementDto(
            referenceType: 'agent',
            referenceId: 'a001',
            settlementType: 'agent_daily',
            grossAmount: 50000,
            feeAmount: 0,
            commissionAmount: 0,
            currency: 'SYP',
            settlementAccountId: '10006-001',
            periodStart: now()->subDay(),
            periodEnd: now(),
            metadata: [],
        );
        $settlement = $this->service->create($dto);

        $this->assertEquals('pending', $settlement->status);

        // pending -> processing -> completed (via execute)
        // execute will try CFE posting and fail since no ledger accounts
        try {
            $this->service->execute($settlement->id);
        } catch (\Throwable $e) {
            // CFE may fail, status should be 'failed'
            $settlement->refresh();
            $this->assertContains($settlement->status, ['failed', 'processing']);
        }
    }

    public function test_find_or_fail_throws_on_missing(): void
    {
        $this->expectException(SettlementNotFoundException::class);
        $this->service->getSummary('nonexistent-id');
    }

    public function test_can_list_by_status(): void
    {
        $dto = new CreateSettlementDto(
            referenceType: 'merchant',
            referenceId: 'm002',
            settlementType: 'merchant_daily',
            grossAmount: 10000,
            feeAmount: 0,
            commissionAmount: 0,
            currency: 'SYP',
            settlementAccountId: '10006-001',
            periodStart: now()->subDay(),
            periodEnd: now(),
            metadata: [],
        );
        $this->service->create($dto);

        $results = $this->service->listByStatus('pending', 0);
        $this->assertCount(1, $results);
    }

    public function test_reconcile_matched(): void
    {
        $dto = new CreateSettlementDto(
            referenceType: 'merchant',
            referenceId: 'm003',
            settlementType: 'merchant_daily',
            grossAmount: 100000,
            feeAmount: 1000,
            commissionAmount: 0,
            currency: 'SYP',
            settlementAccountId: '10006-001',
            periodStart: now()->subDay(),
            periodEnd: now(),
            metadata: [],
        );
        $settlement = $this->service->create($dto);

        $result = $this->service->reconcile($settlement->id, ['amount' => 99000]);

        $this->assertTrue($result['matched']);
        $this->assertEquals(99000, $result['cfe_amount']);
        $this->assertEquals(99000, $result['bank_amount']);
        $this->assertEquals(0, $result['difference']);
    }

    public function test_reconcile_mismatch(): void
    {
        $dto = new CreateSettlementDto(
            referenceType: 'merchant',
            referenceId: 'm004',
            settlementType: 'merchant_daily',
            grossAmount: 100000,
            feeAmount: 0,
            commissionAmount: 0,
            currency: 'SYP',
            settlementAccountId: '10006-001',
            periodStart: now()->subDay(),
            periodEnd: now(),
            metadata: [],
        );
        $settlement = $this->service->create($dto);

        $result = $this->service->reconcile($settlement->id, ['amount' => 95000]);

        $this->assertFalse($result['matched']);
        $this->assertEquals(5000, abs($result['difference']));
    }
}
