<?php

declare(strict_types=1);

namespace Modules\Savings\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Savings\DTOs\CreateSavingsGoalDto;
use Modules\Savings\DTOs\SavingsContributionDto;
use Modules\Savings\Enums\SavingsGoalStatus;
use Modules\Savings\Enums\SavingsTransactionType;
use Modules\Savings\Exceptions\SavingsGoalNotFoundException;
use Modules\Savings\Exceptions\SavingsGoalCompletedException;
use Modules\Savings\Exceptions\InsufficientSavingsBalanceException;
use Modules\Savings\Models\SavingsGoal;
use Modules\Savings\Models\SavingsAccount;
use Modules\Savings\Models\SavingsTransaction;
use Modules\Savings\Models\SavingsProfitRule;
use Modules\Savings\Services\SavingsService;
use Modules\Savings\Services\ProfitDistributionService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class SavingsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private SavingsService $savingsService;
    private ProfitDistributionService $profitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->savingsService = $this->app->make(SavingsService::class);
        $this->profitService = $this->app->make(ProfitDistributionService::class);
    }

    public function test_can_create_savings_goal(): void
    {
        $user = $this->createUser('01ARsavingsUser001');

        $goal = $this->savingsService->createGoal(new CreateSavingsGoalDto(
            userId: $user->id,
            name: 'Hajj 2027',
            nameAr: 'الحج 2027',
            targetAmount: 5000000,
            category: 'religious',
        ));

        $this->assertInstanceOf(SavingsGoal::class, $goal);
        $this->assertEquals(SavingsGoalStatus::ACTIVE->value, $goal->status);
        $this->assertEquals(0, $goal->current_amount);

        $account = SavingsAccount::where('savings_goal_id', $goal->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(0, $account->balance);
    }

    public function test_can_contribute_to_goal(): void
    {
        $user = $this->createUser('01ARsavingsUser002');
        $goal = $this->seedGoal($user->id, 'Car', 2000000);

        $updated = $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 500000,
        ));

        $this->assertEquals(500000, $updated->current_amount);

        $txn = SavingsTransaction::where('savings_goal_id', $goal->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals(SavingsTransactionType::CONTRIBUTION->value, $txn->type);
        $this->assertEquals(500000, $txn->amount);
    }

    public function test_goal_completes_when_target_reached(): void
    {
        $user = $this->createUser('01ARsavingsUser003');
        $goal = $this->seedGoal($user->id, 'Laptop', 500000);

        $result = $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 500000,
        ));

        $this->assertEquals(SavingsGoalStatus::COMPLETED->value, $result->status);
        $this->assertNotNull($result->completed_at);
        $this->assertEquals(100.0, $result->progressPercent());
    }

    public function test_can_withdraw_from_goal(): void
    {
        $user = $this->createUser('01ARsavingsUser004');
        $goal = $this->seedGoal($user->id, 'Emergency Fund', 1000000);

        $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 500000,
        ));

        $result = $this->savingsService->withdraw($goal->id, $user->id, 200000);

        $this->assertEquals(300000, $result->current_amount);
    }

    public function test_throws_on_insufficient_withdrawal(): void
    {
        $user = $this->createUser('01ARsavingsUser005');
        $goal = $this->seedGoal($user->id, 'Test', 500000);

        $this->expectException(InsufficientSavingsBalanceException::class);
        $this->savingsService->withdraw($goal->id, $user->id, 50000);
    }

    public function test_throws_on_missing_goal(): void
    {
        $this->expectException(SavingsGoalNotFoundException::class);
        $this->savingsService->findGoalOrFail('nonexistent');
    }

    public function test_throws_on_completed_goal(): void
    {
        $user = $this->createUser('01ARsavingsUser006');
        $goal = $this->seedGoal($user->id, 'Done', 50000);
        $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 50000,
        ));

        $this->expectException(SavingsGoalCompletedException::class);
        $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 10000,
        ));
    }

    public function test_contribution_below_minimum_throws(): void
    {
        $user = $this->createUser('01ARsavingsUser007');
        $goal = $this->seedGoal($user->id, 'Test', 100000);

        $this->expectException(\InvalidArgumentException::class);
        $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 100,
        ));
    }

    public function test_calculates_daily_profit(): void
    {
        SavingsProfitRule::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Standard Savings',
            'annual_rate' => 5.0,
            'min_balance' => 0,
            'min_duration_days' => 0,
            'is_active' => true,
        ]);

        $user = $this->createUser('01ARsavingsUser008');
        $goal = $this->seedGoal($user->id, 'Profit Test', 10000000);
        $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 1000000,
        ));

        $account = SavingsAccount::where('savings_goal_id', $goal->id)->first();

        $profit = $this->profitService->calculateDailyProfit($account);
        $this->assertGreaterThan(0, $profit);
    }

    public function test_distributes_daily_profit(): void
    {
        SavingsProfitRule::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Standard Savings',
            'annual_rate' => 5.0,
            'calculation_method' => 'daily',
            'distribution_method' => 'daily',
            'min_balance' => 0,
            'min_duration_days' => 0,
            'early_withdrawal_penalty_rate' => 10.0,
            'is_active' => true,
        ]);

        $user = $this->createUser('01ARsavingsUser009');
        $goal = $this->seedGoal($user->id, 'Growth', 5000000);
        $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 2000000,
        ));

        $totalProfit = $this->savingsService->distributeDailyProfit();

        $this->assertGreaterThan(0, $totalProfit);

        $txn = SavingsTransaction::where('type', SavingsTransactionType::PROFIT->value)->first();
        $this->assertNotNull($txn);
        $this->assertGreaterThan(0, $txn->amount);
    }

    public function test_goal_progress_percent(): void
    {
        $user = $this->createUser('01ARsavingsUser010');
        $goal = $this->seedGoal($user->id, 'Progress', 1000000);
        $this->assertEquals(0, $goal->progressPercent());

        $this->savingsService->contribute(new SavingsContributionDto(
            savingsGoalId: $goal->id,
            userId: $user->id,
            amount: 250000,
        ));

        $goal->refresh();
        $this->assertEquals(25.0, $goal->progressPercent());
    }

    /* ──── Helpers ──── */

    private function createUser(string $id, string $phone = '963900000000'): User
    {
        $user = new User();
        $user->id = $id;
        $user->phone = $phone;
        $user->status = 'active';
        $user->save();
        return $user;
    }

    private function seedGoal(string $userId, string $name, int $targetAmount): SavingsGoal
    {
        return $this->savingsService->createGoal(new CreateSavingsGoalDto(
            userId: $userId,
            name: $name,
            targetAmount: $targetAmount,
        ));
    }
}
