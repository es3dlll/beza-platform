# Remittance Testing Strategy

## Test Pyramid

```
          ╱──────╲
        ╱   E2E    ╲         8 tests (critical user journeys)
       ╱──────────────╲
      ╱  Integration    ╲     25 tests (API + DB + FX + Compliance)
     ╱─────────────────────╲
    ╱     Unit Tests         ╲   150+ tests (services, models, rules)
   ╱───────────────────────────╲
```

## Unit Tests

### Remittance Service Tests
```php
class RemittanceServiceTest extends TestCase
{
    /** @test */
    public function it_sends_local_p2p_transfer()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $sender->id, 'currency' => Currency::SYP, 'balance' => 200000,
        ]);
        Wallet::factory()->create([
            'user_id' => $recipient->id, 'currency' => Currency::SYP, 'balance' => 100000,
        ]);

        $result = $this->remittanceService->send(new SendRemittanceRequest(
            sender: $sender,
            beneficiaryId: null,
            recipientId: $recipient->id,
            amount: 50000,
            sourceCurrency: Currency::SYP,
            targetCurrency: Currency::SYP,
            corridorKey: 'SYP_SY->SYP',
        ));

        $this->assertEquals(RemittanceStatus::COMPLETED, $result->status);
        $this->assertEquals(50000, $result->targetAmount);
        $this->assertEquals(250, $result->fee);
        $this->assertDatabaseHas('remittances', [
            'sender_id' => $sender->id,
            'type' => 'local_p2p',
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_sends_diaspora_remittance_with_fx()
    {
        $sender = User::factory()->diaspora()->create();
        $recipient = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $sender->id, 'currency' => Currency::USD, 'balance' => 500000, // cents
        ]);
        Wallet::factory()->create([
            'user_id' => $recipient->id, 'currency' => Currency::SYP, 'balance' => 0,
        ]);

        // Mock FX rate
        $this->fxService->shouldReceive('getLiveRate')
            ->andReturn(new FXRate(12400, 12590, 1.5, null, null, null));

        $result = $this->remittanceService->send(new SendRemittanceRequest(
            sender: $sender,
            beneficiaryId: null,
            recipientId: $recipient->id,
            amount: 50000, // $500.00 in cents
            sourceCurrency: Currency::USD,
            targetCurrency: Currency::SYP,
            corridorKey: 'USD_US->SYP',
        ));

        $this->assertEquals(RemittanceStatus::COMPLETED, $result->status);
        $this->assertEquals(620000000, $result->targetAmount); // 6,200,000 SYP in piasters
    }

    /** @test */
    public function it_blocks_sanctions_matched_transfer()
    {
        $this->expectException(SanctionsBlockException::class);

        $sender = User::factory()->diaspora()->create();
        $recipient = User::factory()->sanctionsMatch()->create();

        $this->remittanceService->send(new SendRemittanceRequest(
            sender: $sender,
            beneficiaryId: null,
            recipientId: $recipient->id,
            amount: 50000,
            sourceCurrency: Currency::USD,
            targetCurrency: Currency::SYP,
            corridorKey: 'USD_US->SYP',
        ));
    }

    /** @test */
    public function it_enforces_daily_limit_per_corridor()
    {
        $this->expectException(DailyLimitExceededException::class);

        $sender = User::factory()->diaspora()->create();
        $recipient = User::factory()->create();

        // Already sent €1,900 today (limit is €2,000)
        CorridorDailyLimit::factory()->create([
            'user_id' => $sender->id,
            'corridor_id' => 1, // EUR_DE->SYP
            'date' => today(),
            'total_sent' => 190000, // €1,900 in cents
        ]);

        $this->remittanceService->send(new SendRemittanceRequest(
            sender: $sender,
            beneficiaryId: null,
            recipientId: $recipient->id,
            amount: 20000, // €200
            sourceCurrency: Currency::EUR,
            targetCurrency: Currency::SYP,
            corridorKey: 'EUR_DE->SYP',
        ));
    }

    /** @test */
    public function it_enforces_idempotency()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $key = Str::uuid();

        // First request — success
        $result = $this->remittanceService->send(new SendRemittanceRequest(
            sender: $sender,
            recipientId: $recipient->id,
            amount: 25000,
            sourceCurrency: Currency::SYP,
            targetCurrency: Currency::SYP,
            corridorKey: 'SYP_SY->SYP',
            idempotencyKey: $key,
        ));

        // Second request with same key — returns existing
        $result2 = $this->remittanceService->send(new SendRemittanceRequest(
            sender: $sender,
            recipientId: $recipient->id,
            amount: 25000,
            sourceCurrency: Currency::SYP,
            targetCurrency: Currency::SYP,
            corridorKey: 'SYP_SY->SYP',
            idempotencyKey: $key,
        ));

        $this->assertEquals($result->remittanceId, $result2->remittanceId);
        $this->assertDatabaseCount('remittances', 1);
    }

    /** @test */
    public function it_emits_events_on_successful_remittance()
    {
        Event::fake();

        // ... setup and execute transfer ...

        Event::assertDispatched(TransferSent::class);
        Event::assertDispatched(RemittanceCompleted::class);
        Event::assertDispatched(TransferReceived::class);
    }

    /** @test */
    public function it_cancels_transfer_within_window()
    {
        // Create a pending transfer
        $remittance = Remittance::factory()->pending()->create([
            'created_at' => now()->subMinutes(5),
        ]);

        $result = $this->remittanceService->cancel($remittance->id);
        $this->assertTrue($result->cancelled);
        $this->assertEquals(RemittanceStatus::CANCELLED, $remittance->fresh()->status);
    }

    /** @test */
    public function it_rejects_cancel_after_30_minutes()
    {
        $remittance = Remittance::factory()->completed()->create([
            'created_at' => now()->subHours(2),
        ]);

        $this->expectException(CannotCancelException::class);
        $this->remittanceService->cancel($remittance->id);
    }
}
```

### Fee Service Tests
```php
class RemittanceFeeServiceTest extends TestCase
{
    /** @test */
    public function local_p2p_fee_is_0_5_percent()
    {
        $fee = $this->feeService->calculateLocalP2PFee(100000, Currency::SYP);
        $this->assertEquals(500, $fee);
    }

    /** @test */
    public function local_p2p_fee_capped_at_5000_syp()
    {
        $fee = $this->feeService->calculateLocalP2PFee(2000000, Currency::SYP);
        $this->assertEquals(5000, $fee);
    }

    /** @test */
    public function diaspora_fee_is_1_5_percent()
    {
        $fee = $this->feeService->calculateDiasporaFee(50000, Currency::USD); // $500
        $this->assertEquals(750, $fee); // $7.50 in cents
    }

    /** @test */
    public function recurring_fee_is_1_0_percent()
    {
        $fee = $this->feeService->calculateRecurringFee(20000, Currency::EUR); // €200
        $this->assertEquals(200, $fee); // €2.00 in cents
    }

    /** @test */
    public function premium_user_gets_discounted_fee()
    {
        $premiumUser = User::factory()->premium()->create();
        $fee = $this->feeService->calculateDiasporaFee(
            50000, Currency::USD, $premiumUser
        );
        $this->assertEquals(375, $fee); // 0.75% instead of 1.5%
    }
}
```

### FX Service Tests
```php
class FXServiceTest extends TestCase
{
    /** @test */
    public function it_returns_live_rate_for_active_corridor()
    {
        $rate = $this->fxService->getLiveRate('EUR_DE->SYP');
        $this->assertNotNull($rate);
        $this->assertGreaterThan(0, $rate->rate);
        $this->assertGreaterThan(0, $rate->midMarketRate);
    }

    /** @test */
    public function it_locks_rate_for_60_seconds()
    {
        $lockId = $this->fxService->lockRate('EUR_DE->SYP', 13200, 42);
        $this->assertNotNull($lockId);

        $locked = $this->fxService->getLockedRate($lockId);
        $this->assertEquals(13200, $locked->rate);
        $this->assertTrue($locked->isLocked());
    }

    /** @test */
    public function locked_rate_expires_after_60_seconds()
    {
        $lockId = $this->fxService->lockRate('EUR_DE->SYP', 13200, 42);

        // Simulate 61 seconds passing
        $this->travel(61)->seconds();

        $this->expectException(FXRateExpiredException::class);
        $this->fxService->getLockedRate($lockId);
    }

    /** @test */
    public function it_converts_amount_correctly()
    {
        $converted = $this->fxService->convert(50000, new FXRate(12400, 12590, 1.5), Currency::USD, Currency::SYP);
        $this->assertEquals(620000000, $converted); // $500 × 12,400 = 6,200,000 SYP (piasters)
    }
}
```

## Integration Tests

### API Tests
```php
class RemittanceApiTest extends TestCase
{
    /** @test */
    public function diaspora_sender_can_send_remittance()
    {
        $sender = User::factory()->diaspora()->kycLevel(2)->create();
        $recipient = User::factory()->create();
        $this->actingAs($sender, 'sanctum');

        $response = $this->postJson('/api/v1/remittance/send', [
            'beneficiary_id' => null,
            'recipient_phone' => $recipient->phone,
            'amount' => 300.00,
            'source_currency' => 'EUR',
            'target_currency' => 'SYP',
            'delivery_method' => 'wallet',
            'note' => 'Test remittance',
            'source_of_funds' => 'salary',
            'pin' => '123456',
        ], ['Idempotency-Key' => Str::uuid()]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonStructure(['data' => [
                'remittance_id', 'status', 'source', 'fx', 'target',
                'beneficiary', 'timeline', 'reference',
            ]]);
    }

    /** @test */
    public function remittance_fails_on_invalid_corridor()
    {
        $sender = User::factory()->diaspora()->create();
        $this->actingAs($sender, 'sanctum');

        $response = $this->postJson('/api/v1/remittance/send', [
            'amount' => 300.00,
            'source_currency' => 'GBP',
            'target_currency' => 'SYP',
            // No corridor for GBP->SYP is active
        ], ['Idempotency-Key' => Str::uuid()]);

        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'CORRECTOR_INACTIVE');
    }

    /** @test */
    public function beneficiary_can_be_created()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/remittance/beneficiaries', [
            'name' => 'أم محمد',
            'relationship' => 'mother',
            'phone' => '+963912345678',
            'city' => 'دمشق',
            'currency_preference' => 'SYP',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'أم محمد');
    }

    /** @test */
    public function recurring_transfer_can_be_created()
    {
        $user = User::factory()->diaspora()->create();
        $beneficiary = Beneficiary::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/remittance/recurring', [
            'beneficiary_id' => $beneficiary->id,
            'amount' => 200.00,
            'source_currency' => 'EUR',
            'target_currency' => 'SYP',
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'pin' => '123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.frequency', 'monthly');
    }
}
```

## E2E Tests (Playwright)

```typescript
// Remittance E2E: Diaspora Sends Money
test('diaspora user can send remittance to Syria', async ({ page }) => {
  // 1. Login as diaspora user (Germany)
  await page.goto('/login');
  await page.fill('[data-testid="phone-input"]', '+4915123456789');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="login-button"]');
  await page.waitForURL('/wallet');

  // 2. Navigate to Send
  await page.click('[data-testid="send-button"]');
  await page.waitForURL('/remittance/send');

  // 3. Select beneficiary
  await page.click('[data-testid="beneficiary-selector"]');
  await page.click('[data-testid="beneficiary-option-am-mohammed"]');

  // 4. Enter amount
  await page.fill('[data-testid="amount-input"]', '300');

  // 5. Verify FX rate displayed
  await expect(page.locator('[data-testid="fx-rate"]')).toBeVisible();
  await expect(page.locator('[data-testid="fee-amount"]')).toContainText('4.50');

  // 6. Lock rate
  await page.click('[data-testid="lock-rate-button"]');
  await expect(page.locator('[data-testid="rate-lock-timer"]')).toBeVisible();

  // 7. Confirm
  await page.click('[data-testid="confirm-remittance-button"]');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="biometric-confirm"]');

  // 8. Success
  await expect(page.locator('[data-testid="success-message"]')).toContainText('تم الإرسال');
  await expect(page.locator('[data-testid="recipient-amount"]')).toContainText('3,960,000');
  await expect(page.locator('[data-testid="receipt-reference"]')).toBeVisible();
});

// E2E: Recurring Transfer Setup
test('user can set up recurring monthly transfer', async ({ page }) => {
  await page.goto('/remittance/recurring/create');

  await page.click('[data-testid="beneficiary-selector"]');
  await page.fill('[data-testid="amount-input"]', '200');
  await page.click('[data-testid="frequency-monthly"]');
  await page.selectOption('[data-testid="day-of-month"]', '1');
  await page.click('[data-testid="duration-ongoing"]');

  await page.click('[data-testid="confirm-recurring-button"]');
  await page.fill('[data-testid="pin-input"]', '123456');

  await expect(page.locator('[data-testid="recurring-success"]')).toContainText('تم إنشاء التحويل الدوري');
  await expect(page.locator('[data-testid="next-execution-date"]')).toBeVisible();
});
```
