<?php

declare(strict_types=1);

namespace Modules\Loyalty\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Loyalty\DTOs\AwardPointsDto;
use Modules\Loyalty\DTOs\RedeemPointsDto;
use Modules\Loyalty\Enums\PointsTransactionType;
use Modules\Loyalty\Exceptions\InsufficientPointsException;
use Modules\Loyalty\Models\LoyaltyPoints;
use Modules\Loyalty\Models\LoyaltyPointsTransaction;
use Modules\Loyalty\Models\LoyaltyTier;
use Modules\Loyalty\Models\CashbackRule;
use Modules\Loyalty\Models\LoyaltyReward;
use Modules\Loyalty\Services\PointsService;
use Modules\Loyalty\Services\CashbackService;
use Modules\Loyalty\Services\TierService;
use Modules\Loyalty\Services\RewardService;
use Modules\Identity\Models\User;
use Tests\TestCase;

final class LoyaltyFeatureTest extends TestCase
{
    use RefreshDatabase;

    private PointsService $pointsService;
    private CashbackService $cashbackService;
    private TierService $tierService;
    private RewardService $rewardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pointsService = $this->app->make(PointsService::class);
        $this->cashbackService = $this->app->make(CashbackService::class);
        $this->tierService = $this->app->make(TierService::class);
        $this->rewardService = $this->app->make(RewardService::class);
    }

    public function test_can_award_points(): void
    {
        $user = $this->createUser('01ARloyUser001');

        $points = $this->pointsService->award(new AwardPointsDto(
            userId: $user->id,
            points: 500,
            description: 'Welcome bonus',
        ));

        $this->assertEquals(500, $points->balance);
        $this->assertEquals(500, $points->lifetime_earned);
        $this->assertEquals('bronze', $points->tier_level);
    }

    public function test_award_applies_tier_multiplier(): void
    {
        $this->seedTiers();
        $user = $this->createUser('01ARloyUser002');

        $this->pointsService->award(new AwardPointsDto(userId: $user->id, points: 4900));
        $this->assertEquals('silver', $this->pointsService->getBalance($user->id)->tier_level);

        // Silver has 1.5x multiplier — next award uses 1.5x
        $this->pointsService->award(new AwardPointsDto(userId: $user->id, points: 100));
        $balance = $this->pointsService->getBalance($user->id);
        $this->assertEquals(4900 + 150, $balance->balance);
        $this->assertEquals('gold', $balance->tier_level); // lifetime_earned=5000 reaches gold
    }

    public function test_can_redeem_points(): void
    {
        $user = $this->createUser('01ARloyUser003');
        $this->pointsService->award(new AwardPointsDto(userId: $user->id, points: 1000));

        $after = $this->pointsService->redeem(new RedeemPointsDto(
            userId: $user->id,
            points: 300,
            description: 'Test redeem',
        ));

        $this->assertEquals(700, $after->balance);
        $this->assertEquals(300, $after->lifetime_redeemed);
    }

    public function test_throws_on_insufficient_points(): void
    {
        $user = $this->createUser('01ARloyUser004');
        $this->pointsService->award(new AwardPointsDto(userId: $user->id, points: 100));

        $this->expectException(InsufficientPointsException::class);
        $this->pointsService->redeem(new RedeemPointsDto(userId: $user->id, points: 500));
    }

    public function test_creates_transaction_on_award(): void
    {
        $user = $this->createUser('01ARloyUser005');
        $this->pointsService->award(new AwardPointsDto(userId: $user->id, points: 200));

        $txn = LoyaltyPointsTransaction::where('user_id', $user->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals(PointsTransactionType::EARNED->value, $txn->type);
        $this->assertEquals(200, $txn->points);
        $this->assertEquals(0, $txn->balance_before);
        $this->assertEquals(200, $txn->balance_after);
    }

    public function test_creates_transaction_on_redeem(): void
    {
        $user = $this->createUser('01ARloyUser006');
        $this->pointsService->award(new AwardPointsDto(userId: $user->id, points: 500));
        $this->pointsService->redeem(new RedeemPointsDto(userId: $user->id, points: 200));

        $txns = LoyaltyPointsTransaction::where('user_id', $user->id)->where('type', 'redeemed')->get();
        $this->assertCount(1, $txns);
        $this->assertEquals(200, $txns->first()->points);
    }

    public function test_tier_determination(): void
    {
        $this->seedTiers();

        $this->assertEquals('bronze', $this->tierService->determineTier(0));
        $this->assertEquals('bronze', $this->tierService->determineTier(999));
        $this->assertEquals('silver', $this->tierService->determineTier(1000));
        $this->assertEquals('gold', $this->tierService->determineTier(5000));
        $this->assertEquals('platinum', $this->tierService->determineTier(10000));
    }

    public function test_cashback_calculation(): void
    {
        $user = $this->createUser('01ARloyUser007');
        $this->seedTiers();

        CashbackRule::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'General Cashback',
            'trigger_type' => 'transaction_amount',
            'rate' => 1.0,
            'min_amount' => 1000,
            'max_cashback' => 5000,
            'is_active' => true,
        ]);

        CashbackRule::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Grocery Bonus',
            'trigger_type' => 'merchant_category',
            'trigger_value' => 'grocery',
            'rate' => 2.0,
            'min_amount' => 0,
            'max_cashback' => 3000,
            'is_active' => true,
        ]);

        $cashback = $this->cashbackService->calculateCashback($user->id, 50000);
        $this->assertEquals(500, $cashback);

        $cashbackGrocery = $this->cashbackService->calculateCashback($user->id, 100000, 'grocery');
        $this->assertEquals(3000, $cashbackGrocery);
    }

    public function test_can_claim_reward(): void
    {
        $user = $this->createUser('01ARloyUser008');
        $this->pointsService->award(new AwardPointsDto(userId: $user->id, points: 2000));

        $reward = LoyaltyReward::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => '500 SYP Cashback',
            'name_ar' => '500 ل.س كاش باك',
            'type' => 'cashback',
            'points_cost' => 1000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $result = $this->rewardService->claimReward($user->id, $reward->id);

        $this->assertEquals($reward->id, $result['reward']->id);
        $this->assertEquals(1000, $result['redeemed_points']);

        $balance = $this->pointsService->getBalance($user->id);
        $this->assertEquals(1000, $balance->balance);
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

    private function seedTiers(): void
    {
        LoyaltyTier::insert([
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'name' => 'Bronze', 'name_ar' => 'برونزي',
                'level' => 'bronze', 'min_points' => 0,
                'points_multiplier' => 1.0, 'cashback_rate' => 0,
                'benefits' => json_encode([]), 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'name' => 'Silver', 'name_ar' => 'فضي',
                'level' => 'silver', 'min_points' => 1000,
                'points_multiplier' => 1.5, 'cashback_rate' => 0.5,
                'benefits' => json_encode(['priority_support']), 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'name' => 'Gold', 'name_ar' => 'ذهبي',
                'level' => 'gold', 'min_points' => 5000,
                'points_multiplier' => 2.0, 'cashback_rate' => 1.0,
                'benefits' => json_encode(['priority_support', 'fee_waiver']), 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'name' => 'Platinum', 'name_ar' => 'بلاتيني',
                'level' => 'platinum', 'min_points' => 10000,
                'points_multiplier' => 3.0, 'cashback_rate' => 2.0,
                'benefits' => json_encode(['priority_support', 'fee_waiver', 'dedicated_manager']), 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }
}
