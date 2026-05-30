<?php

declare(strict_types=1);

namespace Modules\Agent\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Agent\DTOs\RegisterAgentDto;
use Modules\Agent\Exceptions\AgentNotFoundException;
use Modules\Agent\Models\Agent;
use Modules\Agent\Services\AgentService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class AgentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private AgentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(AgentService::class);
    }

    private function createUser(string $id, string $phone = '963900000000'): User
    {
        $user = new User();
        $user->id = $id;
        $user->phone = $phone;
        $user->status = 'active';
        $user->save();
        return $user;
    }

    public function test_can_register_agent(): void
    {
        $this->createUser('01ARagentTestUserId00001', '963111111111');
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
        $this->assertEquals(5000, $agent->coverage_radius);
        $this->assertEquals(100, $agent->liquidity_score);
    }

    public function test_can_approve_agent(): void
    {
        $this->createUser('01ARagentTestUserId00002', '963222222222');
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
        $this->createUser('01ARagentTestUserId00003', '963333333331');
        $this->createUser('01ARagentTestUserId00004', '963333333332');
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
        $this->createUser('01ARagentTestUserId00005', '963444444444');
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
        $this->createUser('01ARagentTestUserId00006', '963555555555');
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
            coverageRadius: 10000,
            altPhone: '963555555556',
        );

        $agent = $this->service->register($dto);

        $this->assertEquals('exchange', $agent->agent_type);
        $this->assertEquals(35.5231, $agent->latitude);
        $this->assertEquals(35.7802, $agent->longitude);
        $this->assertEquals(10000, $agent->coverage_radius);
    }

    public function test_cannot_operate_unapproved_agent(): void
    {
        $this->createUser('01ARagentTestUserId00007', '963666666666');
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

    public function test_can_update_coverage_radius(): void
    {
        $this->createUser('01ARagentTestUserId00008', '963777777777');
        $dto = new RegisterAgentDto(
            userId: '01ARagentTestUserId00008',
            businessName: 'Radius Agent',
            governorate: 'Damascus',
            city: 'Al-Midan',
            phone: '963777777777',
        );

        $agent = $this->service->register($dto);
        $this->assertEquals(5000, $agent->coverage_radius);

        $updated = $this->service->updateCoverageRadius($agent->id, 15000);
        $this->assertEquals(15000, $updated->coverage_radius);
    }

    public function test_can_get_liquidity_score(): void
    {
        $this->createUser('01ARagentTestUserId00009', '963888888888');
        $dto = new RegisterAgentDto(
            userId: '01ARagentTestUserId00009',
            businessName: 'Liquid Agent',
            governorate: 'Homs',
            city: 'Al-Mahatta',
            phone: '963888888888',
        );

        $agent = $this->service->register($dto);
        $this->service->approve($agent->id, 'admin');

        $score = $this->service->getLiquidityScore($agent->id);
        $this->assertArrayHasKey('liquidity_score', $score);
        $this->assertArrayHasKey('cash_balance', $score);
        $this->assertArrayHasKey('electronic_balance', $score);
        $this->assertArrayHasKey('coverage_radius', $score);
        $this->assertEquals(5000, $score['coverage_radius']);
    }

    public function test_nearby_with_coordinates_returns_empty_when_no_agents_nearby(): void
    {
        if (\DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Haversine query requires MySQL (acos not available in SQLite)');
        }

        $this->createUser('01ARagentTestUserId00010', '963999999990');
        $dto = new RegisterAgentDto(
            userId: '01ARagentTestUserId00010',
            businessName: 'Far Agent',
            governorate: 'Aleppo',
            city: 'Al-Soura',
            phone: '963999999990',
            latitude: 36.1,
            longitude: 37.1,
        );

        $agent = $this->service->register($dto);
        $this->service->approve($agent->id, 'admin');

        $nearby = $this->service->getNearby('Aleppo', 35.0, 36.0, 5000);
        $this->assertCount(0, $nearby);
    }
}
