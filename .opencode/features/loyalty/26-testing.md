# Loyalty Testing Strategy

## Test Pyramid
```
          ╱─────╲
        ╱  E2E   ╲         5 tests (loyalty journeys)
       ╱───────────╲
      ╱ Integration  ╲     20 tests (API + DB + Settlements)
     ╱─────────────────╲
    ╱    Unit Tests      ╲   100+ tests (services, models, rules)
   ╱───────────────────────╲
```

## Unit Tests

### PointsService Tests
```php
class PointsServiceTest extends TestCase
{
    /** @test */
    public function it_earns_points_on_transaction()
    {
        $user = User::factory()->create();
        $this->tierService->shouldReceive('getCurrentTier')->andReturn(TierLevel::SILVER);
        $this->tierService->shouldReceive('getMultiplier')->with(TierLevel::SILVER)->andReturn(1.2);

        $result = $this->pointsService->earn(new EarnPointsRequest(
            userId: $user->id,
            transactionAmount: 25000,
            source: 'transfer_send',
            transactionId: 123,
        ));

        $this->assertEquals(30, $result->amount); // 25,000 / 1,000 × 1.2
        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'amount' => 30,
            'type' => 'earned',
            'source' => 'transfer_send',
        ]);
    }

    /** @test */
    public function it_does_not_earn_points_below_threshold()
    {
        $result = $this->pointsService->earn(new EarnPointsRequest(
            userId: 1, transactionAmount: 500, source: 'transfer_send', transactionId: null
        ));
        $this->assertEquals(0, $result->amount);
    }

    /** @test */
    public function it_applies_tier_multiplier()
    {
        $bronze = $this->pointsService->calculatePoints(25000, TierLevel::BRONZE);
        $silver = $this->pointsService->calculatePoints(25000, TierLevel::SILVER);
        $gold = $this->pointsService->calculatePoints(25000, TierLevel::GOLD);
        $platinum = $this->pointsService->calculatePoints(25000, TierLevel::PLATINUM);

        $this->assertEquals(25, $bronze);   // 1.0×
        $this->assertEquals(30, $silver);   // 1.2×
        $this->assertEquals(37, $gold);     // 1.5×
        $this->assertEquals(50, $platinum); // 2.0×
    }
}
```

### TierService Tests
```php
class TierServiceTest extends TestCase
{
    /** @test */
    public function it_upgrades_to_silver_at_10000_points()
    {
        $user = User::factory()->create();
        $this->pointsRepo->shouldReceive('getRolling12MonthTotal')
            ->with($user->id)->andReturn(10000);

        $result = $this->tierService->checkAndUpgrade($user->id);
        $this->assertEquals(TierLevel::SILVER, $result);
    }

    /** @test */
    public function it_upgrades_to_platinum_at_200000_points()
    {
        $user = User::factory()->create();
        $this->pointsRepo->shouldReceive('getRolling12MonthTotal')
            ->with($user->id)->andReturn(200000);

        $result = $this->tierService->checkAndUpgrade($user->id);
        $this->assertEquals(TierLevel::PLATINUM, $result);
    }

    /** @test */
    public function it_does_not_downgrade_within_grace_period()
    {
        // User previously upgraded, still in 30-day grace window
    }
}
```

### RedemptionService Tests
```php
class RedemptionServiceTest extends TestCase
{
    /** @test */
    public function it_redeems_points_successfully()
    {
        $user = User::factory()->create();
        $reward = LoyaltyReward::factory()->feeDiscount()->create();
        $this->pointsRepo->shouldReceive('getBalance')->with($user->id)->andReturn(10000);

        $result = $this->redemptionService->redeem(new RedemptionRequest(
            userId: $user->id,
            rewardId: $reward->id,
            pin: 'valid_pin',
        ));

        $this->assertNotNull($result->couponCode);
        $this->assertEquals('خصم رسوم تحويل 5,000', $result->rewardName);
    }

    /** @test */
    public function it_fails_on_insufficient_points()
    {
        $this->expectException(InsufficientPointsException::class);
        $reward = LoyaltyReward::factory()->create(['point_cost' => 5000]);
        $this->pointsRepo->shouldReceive('getBalance')->andReturn(1000);

        $this->redemptionService->redeem(new RedemptionRequest(
            userId: 1, rewardId: $reward->id, pin: 'valid_pin',
        ));
    }
}
```

## Integration Tests

### API Tests
```php
class PointsApiTest extends TestCase
{
    /** @test */
    public function authenticated_user_can_get_points_balance()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/loyalty/points');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['balance', 'syp_value']]);
    }

    /** @test */
    public function user_can_redeem_points()
    {
        $user = User::factory()->create();
        $reward = LoyaltyReward::factory()->create(['point_cost' => 5000]);
        // Seed points
        $this->pointsRepo->incrementBalance($user->id, 10000);

        $response = $this->actingAs($user)->postJson('/api/v1/loyalty/redeem', [
            'reward_id' => $reward->id,
            'pin' => '123456',
        ]);
        $response->assertStatus(201)
            ->assertJsonPath('data.points_spent', 5000);
    }

    /** @test */
    public function unauthenticated_request_is_rejected()
    {
        $response = $this->getJson('/api/v1/loyalty/points');
        $response->assertStatus(401);
    }
}
```

## E2E Tests (Playwright)

```typescript
// Loyalty E2E: Points Earning and Redemption
test('user earns points and redeems for fee discount', async ({ page }) => {
  // 1. Login
  await page.goto('/login');
  await page.fill('[data-testid="phone-input"]', '+96391234567');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="login-button"]');

  // 2. Check initial points
  await page.click('[data-testid="loyalty-tab"]');
  const initialPoints = await page.locator('[data-testid="points-balance"]').textContent();

  // 3. Send money to earn points
  await page.click('[data-testid="send-button"]');
  await page.fill('[data-testid="phone-input"]', '+963987654321');
  await page.fill('[data-testid="amount-input"]', '50000');
  await page.click('[data-testid="confirm-transfer-button"]');
  await page.fill('[data-testid="pin-input"]', '123456');

  // 4. Verify points earned notification
  await expect(page.locator('[data-testid="points-earned-toast"]')).toContainText('+50');

  // 5. Open loyalty hub and redeem
  await page.click('[data-testid="loyalty-tab"]');
  await page.click('[data-testid="redeem-button"]');
  await page.click('[data-testid="reward-item"] >> nth=0'); // First reward

  // 6. Confirm redemption
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="confirm-redemption"]');

  // 7. Verify success
  await expect(page.locator('[data-testid="redemption-success"]')).toBeVisible();
  await expect(page.locator('[data-testid="coupon-code"]')).toBeVisible();
});
```
