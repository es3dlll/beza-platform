<?php

namespace Tests\Feature\Team;

use App\Models\User;
use App\Modules\Team\Models\DelegationLog;
use App\Modules\Team\Models\TeamMember;
use App\Modules\Team\Services\CommissionSplitService;
use Database\Factories\TeamFactory;
use Database\Factories\TeamMemberFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private User $masterAgent;

    private User $subAgent;

    private User $juniorAgent;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAgent = User::factory()->create([
            'kyc_level' => 3,
            'role' => 'agent',
            'phone' => '0910000001',
        ]);

        $this->subAgent = User::factory()->create([
            'kyc_level' => 2,
            'role' => 'agent',
            'phone' => '0910000002',
        ]);

        $this->juniorAgent = User::factory()->create([
            'kyc_level' => 2,
            'role' => 'agent',
            'phone' => '0910000003',
        ]);

        $this->token = $this->masterAgent->createToken('test-token', ['wap'])->plainTextToken;
    }

    private function auth(): self
    {
        return $this->withToken($this->token);
    }

    public function test_create_team_successfully(): void
    {
        $response = $this->auth()->postJson('/api/v1/agents/teams', [
            'name' => 'Al Andalus Team',
            'description' => 'Main transfer team',
            'max_depth' => 3,
            'master_commission_rate' => 60,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['id', 'name', 'members']]);

        $this->assertDatabaseHas('teams', [
            'name' => 'Al Andalus Team',
            'owner_id' => $this->masterAgent->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('team_members', [
            'user_id' => $this->masterAgent->id,
            'role' => 'master',
            'level' => 0,
        ]);

        $this->assertDatabaseHas('delegation_logs', [
            'action' => 'granted',
            'grantee_id' => $this->masterAgent->id,
        ]);
    }

    public function test_create_team_fails_with_low_kyc(): void
    {
        $lowKycUser = User::factory()->create(['kyc_level' => 2]);
        $token = $lowKycUser->createToken('test-token', ['wap'])->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/agents/teams', [
            'name' => 'Test Team',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'TEAM_CREATION_FAILED');
    }

    public function test_create_team_validates_required_fields(): void
    {
        $response = $this->auth()->postJson('/api/v1/agents/teams', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_list_teams_shows_owned_teams(): void
    {
        TeamFactory::new()->create(['owner_id' => $this->masterAgent->id, 'name' => 'Team A']);
        TeamFactory::new()->create(['owner_id' => $this->masterAgent->id, 'name' => 'Team B']);

        $response = $this->auth()->getJson('/api/v1/agents/teams');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_teams_includes_member_teams(): void
    {
        $team = TeamFactory::new()->create(['owner_id' => $this->masterAgent->id]);
        TeamMemberFactory::new()->create([
            'team_id' => $team->id,
            'user_id' => $this->subAgent->id,
            'role' => 'sub_agent',
            'level' => 1,
        ]);

        $subToken = $this->subAgent->createToken('test-token', ['wap'])->plainTextToken;
        $response = $this->withToken($subToken)->getJson('/api/v1/agents/teams');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_show_team_returns_detail(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
            'name' => 'Detail Team',
        ]);

        $response = $this->auth()->getJson("/api/v1/agents/teams/{$team->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Detail Team');
    }

    public function test_show_team_returns_404(): void
    {
        $response = $this->auth()->getJson('/api/v1/agents/teams/99999');

        $response->assertNotFound();
    }

    public function test_add_member_successfully(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $response = $this->auth()->postJson("/api/v1/agents/teams/{$team->id}/members", [
            'user_id' => $this->subAgent->id,
            'commission_rate' => 30,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'sub_agent');

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->subAgent->id,
            'level' => 1,
        ]);

        $this->assertDatabaseHas('delegation_logs', [
            'team_id' => $team->id,
            'grantee_id' => $this->subAgent->id,
            'action' => 'granted',
        ]);
    }

    public function test_add_member_fails_if_already_in_team(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
        ]);

        TeamMemberFactory::new()->create([
            'team_id' => $team->id,
            'user_id' => $this->subAgent->id,
        ]);

        $response = $this->auth()->postJson("/api/v1/agents/teams/{$team->id}/members", [
            'user_id' => $this->subAgent->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'MEMBER_ADD_FAILED');
    }

    public function test_add_member_fails_with_low_kyc(): void
    {
        $lowKycUser = User::factory()->create(['kyc_level' => 1]);

        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $response = $this->auth()->postJson("/api/v1/agents/teams/{$team->id}/members", [
            'user_id' => $lowKycUser->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'MEMBER_ADD_FAILED');
    }

    public function test_add_member_fails_exceeding_max_depth(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
            'max_depth' => 1,
        ]);

        $master = TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $sub = TeamMemberFactory::new()->create([
            'team_id' => $team->id,
            'user_id' => $this->subAgent->id,
            'parent_id' => $master->id,
            'level' => 1,
        ]);

        $response = $this->auth()->postJson("/api/v1/agents/teams/{$team->id}/members", [
            'user_id' => $this->juniorAgent->id,
            'parent_id' => $sub->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'MEMBER_ADD_FAILED');
    }

    public function test_add_member_fails_if_not_owner(): void
    {
        $otherUser = User::factory()->create(['kyc_level' => 3]);
        $team = TeamFactory::new()->create(['owner_id' => $otherUser->id]);

        $response = $this->auth()->postJson("/api/v1/agents/teams/{$team->id}/members", [
            'user_id' => $this->subAgent->id,
        ]);

        $response->assertForbidden();
    }

    public function test_remove_member_successfully(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $member = TeamMemberFactory::new()->create([
            'team_id' => $team->id,
            'user_id' => $this->subAgent->id,
        ]);

        $response = $this->auth()->deleteJson("/api/v1/agents/teams/{$team->id}/members/{$member->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('team_members', ['id' => $member->id]);
    }

    public function test_remove_master_member_fails(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        $master = TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $response = $this->auth()->deleteJson("/api/v1/agents/teams/{$team->id}/members/{$master->id}");

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'MEMBER_REMOVE_FAILED');
    }

    public function test_update_commission_successfully(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $member = TeamMemberFactory::new()->create([
            'team_id' => $team->id,
            'user_id' => $this->subAgent->id,
            'commission_rate' => 30,
        ]);

        $response = $this->auth()->putJson(
            "/api/v1/agents/teams/{$team->id}/members/{$member->id}/commission",
            ['commission_rate' => 25],
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.commission_rate', '25.00');

        $this->assertDatabaseHas('delegation_logs', [
            'team_id' => $team->id,
            'action' => 'modified',
            'grantee_id' => $this->subAgent->id,
        ]);
    }

    public function test_update_commission_fails_with_invalid_rate(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $member = TeamMemberFactory::new()->create([
            'team_id' => $team->id,
            'user_id' => $this->subAgent->id,
        ]);

        $response = $this->auth()->putJson(
            "/api/v1/agents/teams/{$team->id}/members/{$member->id}/commission",
            ['commission_rate' => 75],
        );

        $response->assertStatus(422);
    }

    public function test_delegation_logs_returns_audit_trail(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        DelegationLog::create([
            'team_id' => $team->id,
            'granter_id' => $this->masterAgent->id,
            'grantee_id' => $this->subAgent->id,
            'permissions' => ['agent:team_member'],
            'action' => 'granted',
            'reason' => 'Add member',
        ]);

        DelegationLog::create([
            'team_id' => $team->id,
            'granter_id' => $this->masterAgent->id,
            'grantee_id' => $this->subAgent->id,
            'permissions' => ['commission_rate' => 25],
            'action' => 'modified',
            'reason' => 'Update commission',
        ]);

        $response = $this->auth()->getJson("/api/v1/agents/teams/{$team->id}/delegation-logs");

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertEquals('granted', $response['data'][0]['action']);
    }

    public function test_all_endpoints_return_401_without_auth(): void
    {
        $this->getJson('/api/v1/agents/teams')->assertUnauthorized();
        $this->postJson('/api/v1/agents/teams', ['name' => 'test'])->assertUnauthorized();
        $this->getJson('/api/v1/agents/teams/1')->assertUnauthorized();
        $this->postJson('/api/v1/agents/teams/1/members', ['user_id' => 1])->assertUnauthorized();
        $this->deleteJson('/api/v1/agents/teams/1/members/1')->assertUnauthorized();
        $this->putJson('/api/v1/agents/teams/1/members/1/commission', ['commission_rate' => 10])->assertUnauthorized();
        $this->getJson('/api/v1/agents/teams/1/delegation-logs')->assertUnauthorized();
    }

    public function test_commission_split_service_defaults(): void
    {
        $service = $this->app->make(CommissionSplitService::class);

        $defaults = $service->getDefaultSplit();

        $this->assertCount(3, $defaults);
        $this->assertEquals(60.00, $defaults[0]['rate']);
        $this->assertEquals(30.00, $defaults[1]['rate']);
        $this->assertEquals(10.00, $defaults[2]['rate']);
    }

    public function test_commission_split_calculates_correctly(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
        ]);

        $master = TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
            'commission_rate' => 50,
        ]);

        $sub = TeamMemberFactory::new()->create([
            'team_id' => $team->id,
            'user_id' => $this->subAgent->id,
            'commission_rate' => 30,
            'parent_id' => $master->id,
            'level' => 1,
        ]);

        $service = $this->app->make(CommissionSplitService::class);
        $splits = $service->calculateSplit($team, 10000);

        $this->assertCount(2, $splits);
        $this->assertEquals(5000, $splits[0]['amount']);
        $this->assertEquals(3000, $splits[1]['amount']);
    }

    public function test_add_member_inherits_limits_from_parent(): void
    {
        $team = TeamFactory::new()->create([
            'owner_id' => $this->masterAgent->id,
            'max_depth' => 3,
        ]);

        TeamMemberFactory::new()->master()->create([
            'team_id' => $team->id,
            'user_id' => $this->masterAgent->id,
            'daily_deposit_limit' => 10000000,
            'daily_withdrawal_limit' => 5000000,
        ]);

        $response = $this->auth()->postJson("/api/v1/agents/teams/{$team->id}/members", [
            'user_id' => $this->subAgent->id,
            'commission_rate' => 30,
        ]);

        $response->assertCreated();

        $masterMember = TeamMember::where('team_id', $team->id)
            ->where('role', 'master')
            ->first();

        $this->assertNotNull($masterMember, 'Master member should exist');
        $this->assertEquals(10000000, $masterMember->daily_deposit_limit, 'Master deposit limit');

        $subMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $this->subAgent->id)
            ->first();

        $this->assertNotNull($subMember, 'Sub member should exist');
        $this->assertEquals(7000000, $subMember->daily_deposit_limit, 'Sub deposit limit');
        $this->assertEquals(3500000, $subMember->daily_withdrawal_limit, 'Sub withdrawal limit');
    }

    public function test_add_member_fails_if_in_another_team(): void
    {
        $team1 = TeamFactory::new()->create(['owner_id' => $this->masterAgent->id]);
        TeamMemberFactory::new()->master()->create([
            'team_id' => $team1->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $this->auth()->postJson("/api/v1/agents/teams/{$team1->id}/members", [
            'user_id' => $this->subAgent->id,
        ])->assertCreated();

        $team2 = TeamFactory::new()->create(['owner_id' => $this->masterAgent->id]);
        TeamMemberFactory::new()->master()->create([
            'team_id' => $team2->id,
            'user_id' => $this->masterAgent->id,
        ]);

        $response = $this->auth()->postJson("/api/v1/agents/teams/{$team2->id}/members", [
            'user_id' => $this->subAgent->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'MEMBER_ADD_FAILED');
    }

    public function test_remove_member_returns_404_for_nonexistent(): void
    {
        $team = TeamFactory::new()->create(['owner_id' => $this->masterAgent->id]);

        $response = $this->auth()->deleteJson("/api/v1/agents/teams/{$team->id}/members/99999");

        $response->assertNotFound();
    }

    public function test_update_commission_returns_404_for_nonexistent(): void
    {
        $team = TeamFactory::new()->create(['owner_id' => $this->masterAgent->id]);

        $response = $this->auth()->putJson(
            "/api/v1/agents/teams/{$team->id}/members/99999/commission",
            ['commission_rate' => 10],
        );

        $response->assertNotFound();
    }

    public function test_show_team_returns_delegation_logs_404(): void
    {
        $response = $this->auth()->getJson('/api/v1/agents/teams/99999/delegation-logs');

        $response->assertNotFound();
    }

    public function test_list_teams_returns_empty_array_when_no_teams(): void
    {
        $response = $this->auth()->getJson('/api/v1/agents/teams');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_create_team_fails_with_excessive_commission_rate(): void
    {
        $response = $this->auth()->postJson('/api/v1/agents/teams', [
            'name' => 'Test Team',
            'master_commission_rate' => 150,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['master_commission_rate']);
    }
}
