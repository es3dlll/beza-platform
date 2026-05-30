# Savings Testing Strategy

## Test Levels

### Unit Tests
```php
// Tests/Savings/Unit/GoalServiceTest.php
class GoalServiceTest extends TestCase
{
    /** @test */
    public function it_creates_goal_with_sub_wallet()
    {
        $user = User::factory()->create();
        $request = new CreateGoalRequest(
            name: 'لابتوب جديد',
            targetAmount: 2500000,
            targetDate: now()->addMonths(6),
            type: GoalType::INDIVIDUAL,
        );

        $goal = $this->goalService->create($request, $user);

        $this->assertDatabaseHas('savings_goals', [
            'id' => $goal->id,
            'user_id' => $user->id,
            'name' => 'لابتوب جديد',
            'target_amount' => 2500000,
            'status' => GoalStatus::ACTIVE,
        ]);
        $this->assertNotNull($goal->cfe_sub_account_id);
    }

    /** @test */
    public function it_validates_target_amount_minimum()
    {
        $this->expectException(ValidationException::class);
        $request = new CreateGoalRequest(
            name: 'Small goal',
            targetAmount: 10000, // Below 50,000 minimum
            targetDate: now()->addMonths(1),
        );
        $this->goalService->create($request, User::factory()->create());
    }

    /** @test */
    public function it_completes_goal_when_target_reached()
    {
        $goal = SavingsGoal::factory()->create([
            'target_amount' => 1000000,
            'current_amount' => 1000000,
            'goal_locked' => false,
        ]);

        $this->goalService->complete($goal);

        $this->assertEquals(GoalStatus::COMPLETED, $goal->fresh()->status);
        $this->assertNotNull($goal->fresh()->completed_at);
    }

    /** @test */
    public function it_waits_lock_period_before_completion()
    {
        $goal = SavingsGoal::factory()->create([
            'target_amount' => 1000000,
            'current_amount' => 1000000,
            'goal_locked' => true,
            'lock_release_date' => now()->addDays(7),
        ]);

        $this->goalService->complete($goal);

        $this->assertEquals(GoalStatus::AWAITING_RELEASE, $goal->fresh()->status);
        $this->assertNull($goal->fresh()->completed_at);
    }
}
```

```php
// Tests/Savings/Unit/AutoSaveServiceTest.php
class AutoSaveServiceTest extends TestCase
{
    /** @test */
    public function it_executes_auto_save_for_due_goal()
    {
        $user = User::factory()->create();
        $mainWallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'type' => 'main',
            'balance' => 100000,
        ]);
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'auto_save_enabled' => true,
            'auto_save_amount' => 5000,
            'auto_save_frequency' => 'daily',
            'current_amount' => 50000,
        ]);

        $this->autoSaveService->execute($goal);

        $this->assertEquals(55000, $goal->fresh()->current_amount);
        $this->assertDatabaseHas('savings_transactions', [
            'goal_id' => $goal->id,
            'type' => 'deposit',
            'sub_type' => 'auto_save',
            'amount' => 5000,
        ]);
    }

    /** @test */
    public function it_skips_when_balance_insufficient()
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => User::factory()->create()->id,
            'auto_save_enabled' => true,
            'auto_save_amount' => 5000,
        ]);
        Wallet::factory()->create([
            'user_id' => $goal->user_id,
            'balance' => 1000, // Insufficient
        ]);

        $this->autoSaveService->execute($goal);

        $this->assertEquals(0, $goal->fresh()->current_amount);
        $this->assertDatabaseHas('auto_save_logs', [
            'goal_id' => $goal->id,
            'status' => 'skipped',
        ]);
    }
}
```

### Integration Tests
```php
// Tests/Savings/Integration/RoundUpIntegrationTest.php
class RoundUpIntegrationTest extends TestCase
{
    /** @test */
    public function it_triggers_round_up_on_wallet_transaction()
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'round_up_enabled' => true,
            'current_amount' => 100000,
        ]);
        RoundUpConfig::factory()->create([
            'user_id' => $user->id,
            'enabled' => true,
            'primary_goal_id' => $goal->id,
        ]);

        // Simulate a wallet transaction of 23,500 SYP
        $walletTxn = WalletTransaction::factory()->create([
            'sender_id' => $user->id,
            'amount' => 23500,
            'type' => 'send',
        ]);

        $this->roundUpService->monitorTransaction($walletTxn);

        $this->assertEquals(100500, $goal->fresh()->current_amount);
        $this->assertDatabaseHas('savings_transactions', [
            'goal_id' => $goal->id,
            'type' => 'roundup',
            'amount' => 500,
        ]);
    }
}
```

### API Tests
```php
// Tests/Savings/Api/GoalApiTest.php
class GoalApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_goal()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/savings/goals', [
            'name' => 'لابتوب جديد',
            'target_amount' => 2500000,
            'target_date' => '2026-12-01',
            'pin' => '123456',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'name' => 'لابتوب جديد',
                'target_amount' => 2500000,
                'status' => 'active',
            ],
        ]);
    }

    /** @test */
    public function user_can_deposit_to_goal()
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'current_amount' => 100000,
        ]);
        Wallet::factory()->create([
            'user_id' => $user->id,
            'type' => 'main',
            'balance' => 500000,
        ]);
        $this->actingAs($user);

        $response = $this->postJson("/api/v1/savings/goals/{$goal->id}/deposit", [
            'amount' => 50000,
            'pin' => '123456',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(150000, $goal->fresh()->current_amount);
    }

    /** @test */
    public function early_withdrawal_charges_penalty()
    {
        $user = User::factory()->create();
        $goal = SavingsGoal::factory()->create([
            'user_id' => $user->id,
            'goal_locked' => true,
            'lock_release_date' => now()->addMonth(),
            'current_amount' => 500000,
        ]);
        $this->actingAs($user);

        $response = $this->postJson("/api/v1/savings/goals/{$goal->id}/withdraw", [
            'amount' => 100000,
            'pin' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.penalty', 2000); // 2% of 100,000
        $response->assertJsonPath('data.net_amount', 98000);
    }
}
```

### Feature Tests (End-to-End)
```php
// Tests/Savings/Feature/ProfitDistributionTest.php
class ProfitDistributionTest extends TestCase
{
    /** @test */
    public function profit_is_distributed_proportionally()
    {
        // Arrange: 3 goals with different amounts
        $goalA = SavingsGoal::factory()->create(['current_amount' => 20000000, 'created_at' => now()->subDays(60)]);
        $goalB = SavingsGoal::factory()->create(['current_amount' => 10000000, 'created_at' => now()->subDays(30)]);
        $goalC = SavingsGoal::factory()->create(['current_amount' => 20000000, 'created_at' => now()->subDays(90)]);

        // Mock CFE return
        $this->mock(CfeService::class)
            ->shouldReceive('getPoolReturn')
            ->andReturn(150000);

        // Act
        $result = $this->profitShareService->calculateMonthly();

        // Assert
        $this->assertEquals(50000000, $result->poolTotal);
        $this->assertEquals(135000, $result->profitPool); // After 10% mgmt fee

        $distA = collect($result->distributions)->firstWhere('goal_id', $goalA->id);
        $distB = collect($result->distributions)->firstWhere('goal_id', $goalB->id);
        $distC = collect($result->distributions)->firstWhere('goal_id', $goalC->id);

        // Goal A: weight 0.4 (20/50), time_weight 1.0 (60d) → 0.4 * 135000 = 54000
        $this->assertEquals(54000, $distA['amount']);

        // Goal B: weight 0.2 (10/50), time_weight 0.5 (15d) → 0.2 * 0.5 * 135000 = 13500
        $this->assertEquals(13500, $distB['amount']);

        // Goal C: weight 0.4 (20/50), time_weight 1.0 (90d) → 0.4 * 135000 = 54000
        $this->assertEquals(54000, $distC['amount']);
    }
}
```

## Test Data Factories

```php
// database/factories/SavingsGoalFactory.php
class SavingsGoalFactory extends Factory
{
    protected $model = SavingsGoal::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => 1,
            'name' => fake('ar_SA')->word() . ' goal',
            'target_amount' => fake()->numberBetween(50000, 5000000),
            'current_amount' => fake()->numberBetween(0, 500000),
            'currency' => 'SYP',
            'type' => 'individual',
            'auto_save_enabled' => fake()->boolean(30),
            'auto_save_frequency' => 'daily',
            'auto_save_amount' => fake()->numberBetween(1000, 50000),
            'round_up_enabled' => fake()->boolean(20),
            'goal_locked' => fake()->boolean(50),
            'status' => GoalStatus::ACTIVE,
            'target_date' => fake()->dateTimeBetween('+1 month', '+12 months'),
            'cfe_sub_account_id' => 'cfe_sub_' . fake()->unique()->uuid(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attrs) => [
            'current_amount' => $attrs['target_amount'],
            'status' => GoalStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attrs) => [
            'goal_locked' => true,
            'lock_release_date' => now()->addMonth(),
        ]);
    }
}
```

## Performance Tests

```php
// Tests/Savings/Performance/MassAutoSaveTest.php
class MassAutoSaveTest extends TestCase
{
    /**
     * @test
     * @group performance
     */
    public function auto_save_batch_processes_500_goals_under_60_seconds()
    {
        // Create 500 goals due for auto-save
        SavingsGoal::factory(500)->create([
            'auto_save_enabled' => true,
            'auto_save_frequency' => 'daily',
            'auto_save_amount' => 5000,
            'status' => GoalStatus::ACTIVE,
        ]);

        $start = microtime(true);
        $processed = $this->autoSaveService->processScheduled();
        $duration = microtime(true) - $start;

        $this->assertEquals(500, $processed);
        $this->assertLessThan(60, $duration, "Batch processing took {$duration}s, expected < 60s");
    }
}
```
