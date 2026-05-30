# FX Engine Testing Strategy

## Test Pyramid

```
          ╱─────╲
        ╱  E2E   ╲         5 tests (critical user journeys)
       ╱───────────╲
      ╱ Integration  ╲     15 tests (API + DB + CFE + Redis)
     ╱─────────────────╲
    ╱    Unit Tests      ╲   80+ tests (services, providers, models, rules)
   ╱───────────────────────╲
```

## Unit Tests

### RateProviderService Tests
```php
class RateProviderServiceTest extends TestCase
{
    /** @test */
    public function it_fetches_rate_from_primary_provider()
    {
        $pair = CurrencyPair::SYP_USD;
        $provider = FxRateProvider::factory()->create([
            'priority' => 1,
            'status' => 'active',
        ]);

        $rate = $this->rateProviderService->fetchRates($pair);

        $this->assertInstanceOf(RateResult::class, $rate);
        $this->assertEquals($pair, $rate->pair);
        $this->assertGreaterThan(0, $rate->mid);
        $this->assertLessThan($rate->ask, $rate->bid);
    }

    /** @test */
    public function it_cascades_to_next_provider_when_primary_fails()
    {
        $primary = FxRateProvider::factory()->create([
            'priority' => 1,
            'handler_class' => FailingProvider::class, // Throws always
        ]);
        $secondary = FxRateProvider::factory()->create([
            'priority' => 2,
            'handler_class' => WorkingProvider::class,
        ]);

        $rate = $this->rateProviderService->fetchRates(CurrencyPair::SYP_USD);

        $this->assertNotNull($rate);
        $this->assertEquals('WorkingProvider', $rate->source);
        $this->assertEquals(1, $secondary->fresh()->consecutive_failures);
    }

    /** @test */
    public function it_throws_when_all_providers_fail()
    {
        FxRateProvider::factory()->count(3)->create([
            'handler_class' => FailingProvider::class,
        ]);

        $this->expectException(AllProvidersDownException::class);
        $this->rateProviderService->fetchRates(CurrencyPair::SYP_USD);
    }

    /** @test */
    public function it_validates_rate_before_accepting()
    {
        $provider = FxRateProvider::factory()->create([
            'handler_class' => AnomalousProvider::class, // Returns 0 mid rate
        ]);

        $this->expectException(RateAnomalyException::class);
        $this->rateProviderService->fetchRates(CurrencyPair::SYP_USD);
    }

    /** @test */
    public function it_skips_circuit_breaker_providers()
    {
        $open = FxRateProvider::factory()->circuitBreakerOpen()->create(['priority' => 1]);
        $working = FxRateProvider::factory()->create(['priority' => 2]);

        $rate = $this->rateProviderService->fetchRates(CurrencyPair::SYP_USD);

        $this->assertEquals($working->name, $rate->source);
        $this->assertTrue($open->fresh()->circuit_breaker_until->isFuture());
    }
}
```

### RateEngine Tests
```php
class RateEngineTest extends TestCase
{
    /** @test */
    public function it_applies_correct_spread_for_user_tier()
    {
        $midRate = 14550.0;
        $standardUser = User::factory()->kycLevel(1)->create(); // standard tier = 3%

        $bezaRate = $this->rateEngine->getLiveRate(CurrencyPair::SYP_USD, $standardUser);

        $expectedRate = 14550 * (1 + 0.03);
        $this->assertEquals($expectedRate, $bezaRate->bezaRate);
        $this->assertEquals(0.03, $bezaRate->spread);
    }

    /** @test */
    public function premium_users_get_discounted_spread()
    {
        $premiumUser = User::factory()->kycLevel(3)->create(); // premium tier = 1.5%

        $bezaRate = $this->rateEngine->getLiveRate(CurrencyPair::SYP_USD, $premiumUser);

        $expectedRate = 14550 * (1 + 0.015);
        $this->assertEquals($expectedRate, $bezaRate->bezaRate);
        $this->assertEquals(0.015, $bezaRate->spread);
    }

    /** @test */
    public function spread_never_exceeds_maximum()
    {
        $basicUser = User::factory()->kycLevel(0)->create(); // basic tier = 4%
        // Override config to test max cap
        config(['fx.max_spread' => 0.03]);

        $bezaRate = $this->rateEngine->getLiveRate(CurrencyPair::SYP_USD, $basicUser);

        $this->assertLessThanOrEqual(0.03, $bezaRate->spread);
    }

    /** @test */
    public function it_returns_cached_rate_within_ttl()
    {
        $pair = CurrencyPair::SYP_USD;
        $cachedRate = new BezaRate($pair, 14550, 14400, 14700, 14935, 0.03, [], now());
        $this->cache->setRate($pair, $cachedRate);

        $rate = $this->rateEngine->getLiveRate($pair);

        $this->assertEquals(14935, $rate->bezaRate);
        // Provider should not be called (verify mock)
        $this->rateProviderService->shouldNotHaveReceived('fetchRates');
    }

    /** @test */
    public function it_fetches_new_rate_when_cache_stale()
    {
        $pair = CurrencyPair::SYP_USD;
        $staleRate = new BezaRate($pair, 14550, 14400, 14700, 14935, 0.03, [], now()->subSeconds(16));
        $this->cache->setRate($pair, $staleRate);

        $this->rateProviderService->shouldReceive('fetchRates')->once()->andReturn(new RateResult(...));

        $rate = $this->rateEngine->getLiveRate($pair);
        $this->assertNotNull($rate);
    }
}
```

### RateLockService Tests
```php
class RateLockServiceTest extends TestCase
{
    /** @test */
    public function it_acquires_lock_atomically()
    {
        $lock = $this->rateLockService->lockRate(new LockRateRequest(
            userId: 42,
            pair: CurrencyPair::SYP_USD,
            rate: Money::of(14935, Currency::SYP),
            amount: Money::of(5000000, Currency::SYP),
        ));

        $this->assertNotNull($lock->lockId);
        $this->assertEquals(30, $lock->remainingSeconds);
        $this->assertDatabaseHas('fx_rate_locks', [
            'lock_id' => $lock->lockId,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_locks()
    {
        $this->rateLockService->lockRate(new LockRateRequest(
            userId: 42,
            pair: CurrencyPair::SYP_USD,
            rate: Money::of(14935, Currency::SYP),
            amount: Money::of(5000000, Currency::SYP),
        ));

        $this->expectException(RateLockConflictException::class);
        $this->rateLockService->lockRate(new LockRateRequest(
            userId: 42,
            pair: CurrencyPair::SYP_USD,
            rate: Money::of(14950, Currency::SYP),
            amount: Money::of(3000000, Currency::SYP),
        ));
    }

    /** @test */
    public function it_marks_lock_as_used_on_conversion()
    {
        $lock = $this->createActiveLock();

        $result = $this->rateLockService->useLock($lock->lockId, 'conv_abc123');

        $this->assertTrue($result);
        $this->assertDatabaseHas('fx_rate_locks', [
            'lock_id' => $lock->lockId,
            'status' => 'used',
            'transaction_id' => 'conv_abc123',
        ]);
    }

    /** @test */
    public function it_rejects_expired_locks()
    {
        $lock = $this->createExpiredLock();

        $result = $this->rateLockService->useLock($lock->lockId, 'conv_abc123');

        $this->assertFalse($result);
        $this->assertDatabaseHas('fx_rate_locks', [
            'lock_id' => $lock->lockId,
            'status' => 'expired',
        ]);
    }

    /** @test */
    public function lock_auto_expires_after_ttl()
    {
        $lock = $this->rateLockService->lockRate(new LockRateRequest(
            userId: 42,
            pair: CurrencyPair::SYP_USD,
            rate: Money::of(14935, Currency::SYP),
            amount: Money::of(5000000, Currency::SYP),
        ));

        // Simulate TTL expiry
        $this->travel(31)->seconds();

        $this->assertTrue($lock->fresh()->isExpired());
    }
}
```

### RateAnomalyService Tests
```php
class RateAnomalyServiceTest extends TestCase
{
    /** @test */
    public function it_detects_spread_widening()
    {
        // Seed recent rates with small spread
        for ($i = 0; $i < 10; $i++) {
            FxRate::factory()->create([
                'pair' => 'SYP/USD',
                'spread_pct' => 0.02,
                'recorded_at' => now()->subSeconds($i * 6),
            ]);
        }
        // Latest rate with large spread
        FxRate::factory()->create([
            'pair' => 'SYP/USD',
            'spread_pct' => 0.06,
            'recorded_at' => now(),
        ]);

        $anomalies = $this->anomalyService->detectAnomalies();

        $this->assertCount(1, $anomalies);
        $this->assertEquals('SPREAD_WIDENING', $anomalies[0]->type);
    }

    /** @test */
    public function it_detects_price_spike()
    {
        FxRate::factory()->create([
            'pair' => 'SYP/USD',
            'mid' => 14500,
            'recorded_at' => now()->subSeconds(30),
        ]);
        FxRate::factory()->create([
            'pair' => 'SYP/USD',
            'mid' => 16000, // 10.3% spike > 5% threshold
            'recorded_at' => now(),
        ]);

        $anomalies = $this->anomalyService->detectAnomalies();

        $this->assertCount(1, $anomalies);
        $this->assertEquals('PRICE_SPIKE', $anomalies[0]->type);
    }

    /** @test */
    public function no_anomaly_when_rates_are_stable()
    {
        FxRate::factory()->count(10)->create([
            'pair' => 'SYP/USD',
            'spread_pct' => 0.025,
            'mid' => 14500,
            'recorded_at' => fn() => now()->subSeconds(rand(1, 60)),
        ]);

        $anomalies = $this->anomalyService->detectAnomalies();

        $this->assertEmpty($anomalies);
    }
}
```

## Integration Tests

### API Tests
```php
class FXApiTest extends TestCase
{
    /** @test */
    public function authenticated_user_can_get_live_rates()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/v1/fx/rates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['rates' => [
                    'SYP/USD' => ['pair', 'bid', 'ask', 'mid', 'beza_rate', 'spread_pct', 'last_updated'],
                ]],
            ]);
    }

    /** @test */
    public function user_can_lock_rate()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // First get a rate to know the correct value
        $rates = $this->getJson('/api/v1/fx/rates')->json('data.rates');
        $rate = $rates['SYP/USD']['beza_rate'];

        $response = $this->postJson('/api/v1/fx/lock', [
            'pair' => 'SYP/USD',
            'amount' => 5000000,
            'rate' => $rate,
        ], ['Idempotency-Key' => Str::uuid()]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure(['data' => ['lock_id', 'pair', 'rate', 'expires_at', 'remaining_seconds']]);
    }

    /** @test */
    public function user_can_execute_conversion()
    {
        $user = User::factory()->create();
        $senderWallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency' => Currency::SYP,
            'balance' => 10000000,
        ]);
        $targetWallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'currency' => Currency::USD,
            'balance' => 0,
        ]);
        $this->actingAs($user, 'sanctum');

        // Lock rate first
        $lockResponse = $this->postJson('/api/v1/fx/lock', [
            'pair' => 'SYP/USD',
            'amount' => 5000000,
            'rate' => 14935,
        ]);
        $lockId = $lockResponse->json('data.lock_id');

        // Execute conversion
        $response = $this->postJson('/api/v1/fx/convert', [
            'lock_id' => $lockId,
            'source_wallet_id' => $senderWallet->id,
            'target_wallet_id' => $targetWallet->id,
            'amount' => 5000000,
            'pin' => '123456',
        ], ['Idempotency-Key' => Str::uuid()]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    /** @test */
    public function unauthenticated_request_is_rejected()
    {
        $response = $this->getJson('/api/v1/fx/rates');
        $response->assertStatus(401);
    }

    /** @test */
    public function conversion_requires_valid_lock()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/fx/convert', [
            'lock_id' => 'nonexistent_lock',
            'source_wallet_id' => 1,
            'target_wallet_id' => 2,
            'amount' => 5000000,
            'pin' => '123456',
        ]);

        $response->assertStatus(410);
    }
}
```

## E2E Tests (Playwright)

```typescript
// FX Engine E2E Test: Check Rates and Convert
test('user can view live rates and perform conversion', async ({ page }) => {
  // 1. Login
  await page.goto('/login');
  await page.fill('[data-testid="phone-input"]', '+96391234567');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="login-button"]');
  await page.waitForURL('/wallet');

  // 2. Navigate to Exchange
  await page.click('[data-testid="exchange-tab"]');
  await page.waitForURL('/fx');

  // 3. Verify rate cards visible
  await expect(page.locator('[data-testid="rate-card-SYP/USD"]')).toBeVisible();
  await expect(page.locator('[data-testid="rate-display"]')).toBeVisible();

  // 4. Check expanded detail
  await page.click('[data-testid="rate-card-SYP/USD"]');
  await expect(page.locator('[data-testid="source-breakdown"]')).toBeVisible();
  await expect(page.locator('[data-testid="cbs-official-rate"]')).toBeVisible();

  // 5. Start conversion
  await page.click('[data-testid="convert-fab"]');
  await page.waitForURL('/fx/convert');

  // 6. Select wallets
  await page.selectOption('[data-testid="source-wallet"]', 'SYP');
  await page.selectOption('[data-testid="target-wallet"]', 'USD');

  // 7. Enter amount
  await page.fill('[data-testid="amount-input"]', '5000000');

  // 8. Verify rate preview visible
  await expect(page.locator('[data-testid="rate-preview"]')).toBeVisible();
  await expect(page.locator('[data-testid="output-amount"]')).toContainText('USD');

  // 9. Lock rate
  await page.click('[data-testid="lock-rate-button"]');
  await expect(page.locator('[data-testid="lock-timer"]')).toBeVisible();

  // 10. Confirm with PIN
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="confirm-conversion-button"]');

  // 11. Success
  await expect(page.locator('[data-testid="success-message"]')).toContainText('تم التحويل');
  await expect(page.locator('[data-testid="receipt-rate"]')).toContainText('SYP/USD');
  await expect(page.locator('[data-testid="receipt-reference"]')).toBeVisible();
});

test('rate lock expiry is handled gracefully', async ({ page }) => {
  // Login and navigate to conversion...
  await page.goto('/fx/convert');

  // Lock rate
  await page.click('[data-testid="lock-rate-button"]');
  await expect(page.locator('[data-testid="lock-timer"]')).toBeVisible();

  // Wait for expiry (30 seconds — use clock manipulation in test)
  await page.clock.fastForward(31000);

  // Try to confirm — should show expired message
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="confirm-conversion-button"]');

  // Should show expired error with "get new rate" option
  await expect(page.locator('[data-testid="rate-expired-message"]')).toBeVisible();
  await expect(page.locator('[data-testid="get-new-rate-button"]')).toBeVisible();
});
```

## Load Test Scenario (K6)
```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '2m', target: 100 },  // Ramp up to 100 users
    { duration: '5m', target: 100 },  // Stay at 100 users
    { duration: '2m', target: 200 },  // Ramp to 200
    { duration: '5m', target: 200 },  // Stay at 200
    { duration: '2m', target: 0 },    // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<2000'],
    http_req_failed: ['rate<0.01'],
  },
};

export default function () {
  const token = 'Bearer test_token';

  // 70% of traffic: read rates
  if (Math.random() < 0.7) {
    const res = http.get('http://localhost/api/v1/fx/rates', {
      headers: { Authorization: token },
    });
    check(res, { 'status 200': (r) => r.status === 200 });
  }
  // 20% of traffic: lock rates
  else if (Math.random() < 0.67) { // 20/30 = 67% of remaining
    const payload = JSON.stringify({
      pair: 'SYP/USD',
      amount: 5000000,
      rate: 14935,
    });
    const res = http.post('http://localhost/api/v1/fx/lock', payload, {
      headers: { Authorization: token, 'Content-Type': 'application/json' },
    });
    check(res, { 'lock status 200': (r) => r.status === 200 });
  }
  // 10% of traffic: convert
  else {
    // ...conversion payload
  }

  sleep(1);
}
```
