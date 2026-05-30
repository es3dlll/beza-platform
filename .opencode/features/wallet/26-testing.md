# Wallet Testing Strategy

## Test Pyramid

```
          ╱─────╲
        ╱  E2E   ╲         5 tests (critical user journeys)
       ╱───────────╲
      ╱ Integration  ╲     20 tests (API + DB + CFE)
     ╱─────────────────╲
    ╱    Unit Tests      ╲   100+ tests (services, models, rules)
   ╱───────────────────────╲
```

## Unit Tests

### Service Tests
```php
class TransferServiceTest extends TestCase
{
    /** @test */
    public function it_sends_money_successfully()
    {
        // Arrange
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $senderWallet = Wallet::factory()->create([
            'user_id' => $sender->id, 'currency' => Currency::SYP,
        ]);
        // Create CFE account with 100,000 SYP balance
        $this->cfe->seedBalance($senderWallet->cfe_account_id, 100000);

        // Act
        $result = $this->transferService->send(new SendMoneyRequest(
            sender: $sender,
            recipientPhone: $recipient->phone,
            amount: new Money(25000, Currency::SYP),
            currency: Currency::SYP,
            note: 'Test transfer',
            pinHash: 'valid_pin_hash',
            idempotencyKey: Str::uuid(),
        ));

        // Assert
        $this->assertEquals(TransactionStatus::COMPLETED, $result->status);
        $this->assertDatabaseHas('wallet_transactions', [
            'sender_id' => $sender->id,
            'amount' => 25000,
            'status' => 'completed',
        ]);
        // Verify balances
        $this->assertEquals(74875, $this->cfe->getBalance($senderWallet->cfe_account_id)->available);
    }

    /** @test */
    public function it_fails_on_insufficient_balance()
    {
        $this->expectException(InsufficientBalanceException::class);
        // ... setup with 10,000 balance, try to send 25,000
    }

    /** @test */
    public function it_fails_on_daily_limit_exceeded()
    {
        // ... setup with 490,000 already sent today, try to send 25,000
    }

    /** @test */
    public function it_prevents_self_transfer()
    {
        // ... setup same user as sender and recipient
    }

    /** @test */
    public function it_enforces_idempotency()
    {
        // Send same idempotency key twice → second returns existing txn
    }

    /** @test */
    public function it_emits_events_on_completion()
    {
        // Assert TransferSent and WalletDebited events dispatched
        Event::assertDispatched(TransferSent::class);
        Event::assertDispatched(WalletDebited::class);
    }
}
```

### Fee Service Tests
```php
class FeeServiceTest extends TestCase
{
    /** @test */
    public function it_charges_0_5_percent_for_p2p()
    {
        $fee = $this->feeService->calculateTransferFee(100000, Currency::SYP);
        $this->assertEquals(500, $fee);
    }

    /** @test */
    public function it_caps_fee_at_5000_syp()
    {
        $fee = $this->feeService->calculateTransferFee(2000000, Currency::SYP);
        $this->assertEquals(5000, $fee);
    }

    /** @test */
    public function premium_users_get_free_transfers()
    {
        $premiumUser = User::factory()->premium()->create();
        $fee = $this->feeService->calculateTransferFee(25000, Currency::SYP, $premiumUser);
        $this->assertEquals(0, $fee);
    }
}
```

### Limit Service Tests
```php
class LimitServiceTest extends TestCase
{
    /** @test */
    public function it_enforces_kyc_level_limits()
    {
        $user = User::factory()->kycLevel(0)->create();
        $this->assertEquals(50000, $this->limitService->getDailyLimit($user, Currency::SYP));
    }

    /** @test */
    public function it_calculates_remaining_limit()
    {
        $user = User::factory()->kycLevel(1)->create();
        // Already sent 300,000 today
        $remaining = $this->limitService->getRemainingDailyLimit($user, Currency::SYP, 300000);
        $this->assertEquals(200000, $remaining);
    }
}
```

## Integration Tests

### API Tests
```php
class TransferApiTest extends TestCase
{
    /** @test */
    public function authenticated_user_can_send_money()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $this->actingAs($sender, 'sanctum');

        $response = $this->postJson('/api/v1/wallet/transfer/send', [
            'recipient_phone' => $recipient->phone,
            'amount' => 25000,
            'currency' => 'SYP',
            'pin' => '123456',
            'note' => 'Test',
        ], ['Idempotency-Key' => Str::uuid()]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonStructure(['data' => ['transaction_id', 'status', 'amount', 'fee', 'reference']]);
    }

    /** @test */
    public function unauthenticated_request_is_rejected()
    {
        $response = $this->postJson('/api/v1/wallet/transfer/send', [
            'amount' => 25000,
            'currency' => 'SYP',
        ]);
        $response->assertStatus(401);
    }
}
```

## E2E Tests (Playwright)

```typescript
// Wallet E2E Test: Send Money Flow
test('user can send money successfully', async ({ page }) => {
  // 1. Login
  await page.goto('/login');
  await page.fill('[data-testid="phone-input"]', '+96391234567');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="login-button"]');
  await page.waitForURL('/wallet');

  // 2. Check balance
  await expect(page.locator('[data-testid="balance-amount"]')).toContainText('100,000');

  // 3. Tap Send
  await page.click('[data-testid="send-button"]');

  // 4. Enter recipient
  await page.fill('[data-testid="phone-input"]', '+963987654321');
  await page.fill('[data-testid="amount-input"]', '25000');
  await page.fill('[data-testid="note-input"]', 'Test payment');

  // 5. Verify fee shown
  await expect(page.locator('[data-testid="fee-amount"]')).toContainText('125');

  // 6. Confirm
  await page.click('[data-testid="confirm-transfer-button"]');
  await page.fill('[data-testid="pin-input"]', '123456');

  // 7. Success
  await expect(page.locator('[data-testid="success-message"]')).toContainText('تم الإرسال');
  await expect(page.locator('[data-testid="receipt-reference"]')).toBeVisible();
});
```
