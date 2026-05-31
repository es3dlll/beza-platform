# Cards Testing Strategy

## Test Pyramid

```
          ╱─────╲
        ╱  E2E   ╲         8 tests (critical card journeys)
       ╱───────────╲
      ╱ Integration  ╲     25 tests (API + DB + Switch)
     ╱─────────────────╲
    ╱    Unit Tests      ╲   150+ tests (services, models, rules)
   ╱───────────────────────╲
```

## Unit Tests

### Card Service Tests
```php
class CardServiceTest extends TestCase
{
    /** @test */
    public function it_creates_virtual_card_successfully()
    {
        $user = User::factory()->kycLevel(2)->create();
        $this->cardService->create(new CreateCardRequest(
            user: $user,
            type: CardType::VIRTUAL,
            currency: Currency::SYP,
            limits: ['online' => 500000, 'pos' => 200000, 'atm' => 0, 'international' => 0],
            nickname: 'بطاقة التسوق',
        ));

        $this->assertDatabaseHas('cards', [
            'user_id' => $user->id,
            'card_type' => 'virtual',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_fails_on_kyc_level_1()
    {
        $this->expectException(KycInsufficientException::class);
        $user = User::factory()->kycLevel(1)->create();
        $this->cardService->create(/* ... */);
    }

    /** @test */
    public function it_fails_when_user_exceeds_card_limit()
    {
        $user = User::factory()->kycLevel(2)->create();
        Card::factory()->count(5)->create(['user_id' => $user->id]);
        $this->expectException(MaxCardsExceededException::class);
        $this->cardService->create(/* ... */);
    }

    /** @test */
    public function it_freezes_card()
    {
        $card = Card::factory()->create(['status' => 'active']);
        $this->cardService->freeze($card->id);
        $this->assertEquals('frozen', $card->fresh()->status);
    }

    /** @test */
    public function it_unfreezes_card()
    {
        $card = Card::factory()->create(['status' => 'frozen']);
        $this->cardService->unfreeze($card->id);
        $this->assertEquals('active', $card->fresh()->status);
    }

    /** @test */
    public function it_replaces_card_with_same_pan()
    {
        $oldCard = Card::factory()->physical()->create();
        $newCard = $this->cardService->replace($oldCard->id, 'damaged');
        $this->assertEquals($oldCard->bin, $newCard->bin);
        $this->assertEquals($oldCard->pan_suffix, $newCard->pan_suffix);
        $this->assertNotEquals($oldCard->expiry, $newCard->expiry);
        $this->assertEquals('closed', $oldCard->fresh()->status);
    }
}
```

### Card Limit Service Tests
```php
class CardLimitServiceTest extends TestCase
{
    /** @test */
    public function it_checks_online_limit()
    {
        $card = Card::factory()->create([
            'limits' => ['online' => 500000, 'pos' => 200000, 'atm' => 0, 'international' => 0],
            'spent_today' => 100000,
        ]);
        $result = $this->limitService->checkAuthorization($card, 'online', 300000);
        $this->assertTrue($result->approved);
        $this->assertEquals(400000, $result->remaining);
    }

    /** @test */
    public function it_declines_when_limit_exceeded()
    {
        $card = Card::factory()->create([
            'limits' => ['online' => 500000],
            'spent_today' => 450000,
        ]);
        $result = $this->limitService->checkAuthorization($card, 'online', 100000);
        $this->assertFalse($result->approved);
        $this->assertEquals('limit_exceeded', $result->reason);
    }

    /** @test */
    public function it_declines_when_category_disabled()
    {
        $card = Card::factory()->create([
            'limits' => ['atm' => 0],
        ]);
        $result = $this->limitService->checkAuthorization($card, 'atm', 50000);
        $this->assertFalse($result->approved);
        $this->assertEquals('category_disabled', $result->reason);
    }

    /** @test */
    public function it_resets_daily_counter()
    {
        $card = Card::factory()->create([
            'spent_today' => 300000,
            'spent_today_at' => now()->subDay(),
        ]);
        $this->limitService->resetDailyIfNeeded($card);
        $this->assertEquals(0, $card->spent_today);
    }
}
```

### Card Processor Tests
```php
class CardProcessorTest extends TestCase
{
    /** @test */
    public function it_authorizes_valid_transaction()
    {
        $card = Card::factory()->active()->create();
        $response = $this->processor->authorize(new AuthorizationRequest(
            pan: $card->pan_hash,
            amount: 75000,
            currency: 'SYP',
            merchant: 'Amazon',
            mcc: '5311',
            country: 'US',
            cvv: '123',
            expiry: '12/28',
        ));
        $this->assertEquals('00', $response->responseCode);
        $this->assertNotNull($response->authCode);
    }

    /** @test */
    public function it_declines_frozen_card()
    {
        $card = Card::factory()->frozen()->create();
        $response = $this->processor->authorize(/* ... */);
        $this->assertEquals('05', $response->responseCode);
        $this->assertEquals('card_frozen', $response->declineReason);
    }
}
```

## Integration Tests

### API Tests
```php
class CardApiTest extends TestCase
{
    /** @test */
    public function authenticated_user_can_create_card()
    {
        $user = User::factory()->kycLevel(2)->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/cards/create', [
            'type' => 'virtual',
            'currency' => 'SYP',
            'limits' => ['online' => 500000, 'pos' => 200000, 'atm' => 0, 'international' => 0],
        ], ['Idempotency-Key' => Str::uuid()]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonStructure(['data' => ['id', 'bin', 'last_four', 'expiry', 'type']]);
    }

    /** @test */
    public function user_can_freeze_and_unfreeze_card()
    {
        $user = User::factory()->create();
        $card = Card::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'sanctum');

        // Freeze
        $this->postJson("/api/v1/cards/{$card->id}/freeze", ['pin' => '123456'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'frozen');

        // Unfreeze
        $this->postJson("/api/v1/cards/{$card->id}/unfreeze", ['pin' => '123456'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    /** @test */
    public function card_owner_only_can_manage_card()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $card = Card::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum');
        $this->postJson("/api/v1/cards/{$card->id}/freeze", ['pin' => '123456'])
            ->assertStatus(403);
    }
}
```

## E2E Tests (Playwright)

```typescript
// Card E2E Test: Create and Use Virtual Card
test('user can create and use virtual card', async ({ page }) => {
  // 1. Login
  await page.goto('/login');
  await page.fill('[data-testid="phone-input"]', '+96391234567');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="login-button"]');
  await page.waitForURL('/cards');

  // 2. Create virtual card
  await page.click('[data-testid="create-card-button"]');
  await page.click('[data-testid="card-type-virtual"]');
  await page.fill('[data-testid="card-nickname"]', 'بطاقة التسوق');
  await page.click('[data-testid="confirm-create"]');

  // 3. Verify card created
  await expect(page.locator('[data-testid="card-success"]')).toContainText('تم إنشاء البطاقة');
  await expect(page.locator('[data-testid="card-last-four"]')).toBeVisible();

  // 4. Freeze card
  await page.click('[data-testid="freeze-button"]');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="confirm-freeze"]');
  await expect(page.locator('[data-testid="card-status"]')).toContainText('مجمدة');

  // 5. Unfreeze card
  await page.click('[data-testid="unfreeze-button"]');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="confirm-unfreeze"]');
  await expect(page.locator('[data-testid="card-status"]')).toContainText('نشطة');
});

// Card E2E: One-Time Card
test('user can create one-time card', async ({ page }) => {
  // Login
  await page.goto('/cards/one-time');
  await page.fill('[data-testid="amount-input"]', '75000');
  await page.click('[data-testid="generate-one-time"]');

  await expect(page.locator('[data-testid="one-time-card-pan"]')).toBeVisible();
  await expect(page.locator('[data-testid="one-time-cvv"]')).toBeVisible();
  await expect(page.locator('[data-testid="countdown-timer"]')).toContainText('24:00:00');
});
```
