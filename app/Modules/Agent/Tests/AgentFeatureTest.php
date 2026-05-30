<?php

declare(strict_types=1);

namespace Modules\Agent\Tests;

use Modules\Agent\DTOs\RegisterAgentDto;
use Modules\Agent\Exceptions\AgentNotFoundException;
use Modules\Agent\Models\Agent;
use Modules\Agent\Services\AgentService;
use Tests\TestCase;

final class AgentFeatureTest extends TestCase
{
    private AgentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(AgentService::class);
    }

    public function test_can_register_agent(): void
    {
        $dto = new RegisterAgentDto(
            userId: '01ARagentTestUserId00001',
            businessName: 'Al-Sham Exchange',
            governorate: 'Damascus',
            city: 'Al-Midan',
            phone: '963111111111',
        );

        $agent = $this->service->register($dto);

        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertEquals('pending', $agent->status);
        $this->assertEquals('Al-Sham Exchange', $agent->business_name);
    }

    public function test_can_approve_agent(): void
    {
        $dto = new RegisterAgentDto(
            userId: '01ARagentTestUserId00002',
            businessName: 'Al-Baraka Exchange',
            governorate: 'Aleppo',
            city: 'Al-Aziziyah',
            phone: '963222222222',
        );

        $agent = $this->service->register($dto);
        $approved = $this->service->approve($agent->id, 'admin-001');

        $this->assertEquals('approved', $approved->status);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_throws_exception_for_missing_agent(): void
    {
        $this->expectException(AgentNotFoundException::class);
        $this->service->getTodaySummary('nonexistent');
    }

    public function test_can_get_nearby_agents(): void
    {
        $dto1 = new RegisterAgentDto(
            userId: '01ARagentTestUserId00003',
            businessName: 'Agent 1',
            governorate: 'Damascus',
            city: 'Baramkeh',
            phone: '963333333331',
        );
        $dto2 = new RegisterAgentDto(
            userId: '01ARagentTestUserId00004',
            businessName: 'Agent 2',
            governorate: 'Damascus',
            city: 'Mazzeh',
            phone: '963333333332',
        );

        $a1 = $this->service->register($dto1);
        $a2 = $this->service->register($dto2);

        $this->service->approve($a1->id, 'admin');
        $this->service->approve($a2->id, 'admin');

        $nearby = $this->service->getNearby('Damascus');
        $this->assertCount(2, $nearby);
    }

    public function test_today_summary_returns_zero_initially(): void
    {
        $dto = new RegisterAgentDto(
            userId: '01ARagentTestUserId00005',
            businessName: 'Zero Agent',
            governorate: 'Homs',
            city: 'Al-Waer',
            phone: '963444444444',
        );

        $agent = $this->service->register($dto);
        $this->service->approve($agent->id, 'admin');

        $summary = $this->service->getTodaySummary($agent->id);
        $this->assertEquals(0, $summary['today_cash_in']);
        $this->assertEquals(0, $summary['today_cash_out']);
    }

    public function test_register_with_full_details(): void
    {
        $dto = new RegisterAgentDto(
            userId: '01ARagentTestUserId00006',
            businessName: 'Full Agent',
            governorate: 'Latakia',
            city: 'Al-Ramel',
            phone: '963555555555',
            agentType: 'exchange',
            area: 'Al-Ramel Al-Shamali',
            address: 'Main Street, Building 5',
            latitude: 35.5231,
            longitude: 35.7802,
            altPhone: '963555555556',
        );

        $agent = $this->service->register($dto);

        $this->assertEquals('exchange', $agent->agent_type);
        $this->assertEquals(35.5231, $agent->latitude);
        $this->assertEquals(35.7802, $agent->longitude);
    }

    public function test_cannot_operate_unapproved_agent(): void
    {
        $dto = new RegisterAgentDto(
            userId: '01ARagentTestUserId00007',
            businessName: 'Unapproved',
            governorate: 'Tartous',
            city: 'Al-Mina',
            phone: '963666666666',
        );

        $agent = $this->service->register($dto);

        $this->assertEquals('pending', $agent->status);
    }
}
