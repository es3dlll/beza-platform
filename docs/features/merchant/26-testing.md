# Merchant Testing Strategy

## Test Pyramid

```
          ╱─────╲
        ╱  E2E   ╲         5 tests (critical user journeys)
       ╱───────────╲
      ╱ Integration  ╲     25 tests (API + DB + CFE + QR gen)
     ╱─────────────────╲
    ╱    Unit Tests      ╲   120+ tests (services, models, rules)
   ╱───────────────────────╲
```

## Unit Tests

### MerchantService Tests
```php
class MerchantServiceTest extends TestCase
{
    /** @test */
    public function it_registers_a_new_merchant()
    {
        // Arrange
        $user = User::factory()->create();
        $request = new RegisterMerchantRequest(
            user: $user,
            tenant: Tenant::factory()->create(),
            businessName: 'متجر الشمّام',
            businessType: BusinessType::GROCERY,
            licenseNumber: '12345',
            licenseImage: null,
            shopPhotos: ['photo1.jpg'],
            location: ['lat' => 33.5138, 'lng' => 36.2765],
            customerPhone: '+963912345600',
        );

        // Act
        $merchant = $this->merchantService->register($request);

        // Assert
        $this->assertEquals('متجر الشمّام', $merchant->businessName);
        $this->assertEquals(MerchantStatus::PENDING, $merchant->status);
        $this->assertDatabaseHas('merchants', [
            'id' => $merchant->id,
            'business_name' => 'متجر الشمّام',
        ]);
        $this->assertDatabaseHas('merchant_qr_codes', [
            'merchant_id' => $merchant->id,
            'type' => 'static',
        ]);
    }

    /** @test */
    public function it_verifies_merchant()
    {
        $merchant = Merchant::factory()->pending()->create();
        $result = $this->merchantService->verify($merchant->id, true);
        $this->assertEquals(MerchantStatus::VERIFIED, $result->status);
        $this->assertNotNull($result->verified_at);
    }

    /** @test */
    public function it_rejects_merchant_with_reason()
    {
        $merchant = Merchant::factory()->pending()->create();
        $result = $this->merchantService->verify($merchant->id, false, 'صورة الترخيص غير واضحة');
        $this->assertEquals(MerchantStatus::REJECTED, $result->status);
        $this->assertEquals('صورة الترخيص غير واضحة', $result->rejection_reason);
    }

    /** @test */
    public function it_calculates_tier_correctly()
    {
        $micro = $this->merchantService->register($this->makeRequest(['licenseNumber' => null]));
        $this->assertEquals(MerchantTier::MICRO, $micro->tier);

        $small = $this->merchantService->register($this->makeRequest(['licenseNumber' => '12345']));
        $this->assertEquals(MerchantTier::SMALL, $small->tier);
    }
}
```

### QrService Tests
```php
class QrServiceTest extends TestCase
{
    /** @test */
    public function it_generates_static_qr()
    {
        $merchant = Merchant::factory()->verified()->create();
        $qr = $this->qrService->generateStaticQr($merchant);

        $this->assertEquals(QrType::STATIC, $qr->type);
        $this->assertNull($qr->amount);
        $this->assertStringContainsString("merchant/{$merchant->id}", $qr->qr_data);
        $this->assertStringEndsWith('.png', $qr->image_url);
    }

    /** @test */
    public function it_generates_dynamic_qr_with_amount()
    {
        $merchant = Merchant::factory()->verified()->create();
        $qr = $this->qrService->generateDynamicQr($merchant, 45000, now()->addHours(1));

        $this->assertEquals(QrType::DYNAMIC, $qr->type);
        $this->assertEquals(45000, $qr->amount);
        $this->assertStringContainsString('amount=45000', $qr->qr_data);
    }

    /** @test */
    public function it_increments_scan_count()
    {
        $qr = MerchantQrCode::factory()->create(['scan_count' => 0]);
        $this->qrService->serveQrImage($qr->id);
        $this->assertEquals(1, $qr->fresh()->scan_count);
    }
}
```

### PaymentLinkService Tests
```php
class PaymentLinkServiceTest extends TestCase
{
    /** @test */
    public function it_creates_payment_link()
    {
        $merchant = Merchant::factory()->verified()->create();
        $link = $this->paymentLinkService->create(
            merchant: $merchant,
            amount: 45000,
            description: 'شنطة ظهر جلدية',
            expiresIn: 3600,
        );

        $this->assertEquals(PaymentLinkStatus::PENDING, $link->status);
        $this->assertEquals(45000, $link->amount->amount);
        $this->assertEquals('شنطة ظهر جلدية', $link->description);
        $this->assertStringContainsString('pay.beza.com', $link->shortUrl);
    }

    /** @test */
    public function it_prevents_payment_of_expired_link()
    {
        $link = PaymentLink::factory()->expired()->create();
        $this->expectException(PaymentLinkExpiredException::class);
        $this->paymentLinkService->processPayment($link, User::factory()->create());
    }

    /** @test */
    public function it_prevents_double_payment()
    {
        $link = PaymentLink::factory()->paid()->create();
        $this->expectException(PaymentLinkAlreadyPaidException::class);
        $this->paymentLinkService->processPayment($link, User::factory()->create());
    }
}
```

### SettlementService Tests
```php
class SettlementServiceTest extends TestCase
{
    /** @test */
    public function it_processes_daily_settlement()
    {
        $merchant = Merchant::factory()->verified()->create();
        MerchantTransaction::factory()->count(5)->create([
            'merchant_id' => $merchant->id,
            'amount' => 100000,
            'mdr_rate' => 1.5,
            'mdr_amount' => 1500,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $settlement = $this->settlementService->processMerchantSettlement($merchant);

        $this->assertEquals(500000, $settlement->grossAmount->amount);
        $this->assertEquals(7500, $settlement->mdrAmount->amount);
        $this->assertEquals(492500, $settlement->netAmount->amount);
        $this->assertEquals(SettlementStatus::COMPLETED, $settlement->status);
        $this->assertEquals(5, $settlement->transactionCount);
    }

    /** @test */
    public function it_skips_merchant_with_no_transactions()
    {
        $merchant = Merchant::factory()->verified()->create();
        $this->expectException(\Exception::class);
        $this->settlementService->processMerchantSettlement($merchant);
    }

    /** @test */
    public function it_handles_mixed_mdr_rates()
    {
        $merchant = Merchant::factory()->verified()->create();
        MerchantTransaction::factory()->create([
            'merchant_id' => $merchant->id,
            'amount' => 100000,
            'method' => 'qr',
            'mdr_rate' => 1.5,
            'mdr_amount' => 1500,
        ]);
        MerchantTransaction::factory()->create([
            'merchant_id' => $merchant->id,
            'amount' => 100000,
            'method' => 'pos',
            'mdr_rate' => 2.0,
            'mdr_amount' => 2000,
        ]);

        $settlement = $this->settlementService->processMerchantSettlement($merchant);
        $this->assertEquals(200000, $settlement->grossAmount->amount);
        $this->assertEquals(3500, $settlement->mdrAmount->amount);
    }
}
```

## Integration Tests

### API Tests
```php
class MerchantApiTest extends TestCase
{
    /** @test */
    public function merchant_can_register()
    {
        $response = $this->postJson('/api/v1/merchant/register', [
            'phone' => '+963912345678',
            'pin' => '123456',
            'business_name' => 'متجر الشمّام',
            'business_type' => 'grocery',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.business_name', 'متجر الشمّام')
            ->assertJsonStructure(['data' => ['merchant_id', 'qr_code', 'status']]);
    }

    /** @test */
    public function authenticated_merchant_can_generate_qr()
    {
        $merchant = $this->actingAsMerchant();
        $response = $this->postJson('/api/v1/merchant/qr/generate', [
            'type' => 'dynamic',
            'amount' => 45000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'dynamic')
            ->assertJsonPath('data.amount', 45000);
    }

    /** @test */
    public function merchant_can_create_payment_link()
    {
        $merchant = $this->actingAsMerchant();
        $response = $this->postJson('/api/v1/merchant/payment-link', [
            'amount' => 45000,
            'description' => 'شنطة ظهر جلدية',
            'expires_in' => 3600,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonStructure(['data' => ['short_url', 'link_id']]);
    }

    /** @test */
    public function unauthenticated_request_is_rejected()
    {
        $response = $this->postJson('/api/v1/merchant/payment-link', [
            'amount' => 45000,
        ]);
        $response->assertStatus(401);
    }
}
```

## E2E Tests (Playwright)

```typescript
// Merchant E2E Test: QR Payment Flow
test('customer can pay merchant via QR', async ({ page }) => {
  // 1. Merchant logs in and views QR
  await page.goto('/merchant/login');
  await page.fill('[data-testid="phone-input"]', '+96391234567');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="login-button"]');
  await page.waitForURL('/merchant');
  await page.click('[data-testid="show-qr-button"]');

  // 2. Verify QR is displayed
  await expect(page.locator('[data-testid="qr-image"]')).toBeVisible();
  await expect(page.locator('[data-testid="merchant-name"]')).toContainText('متجر الشمّام');

  // 3. Switch to customer (new browser context)
  // Note: In real test, use a different context/tab
  const customerPage = await context.newPage();
  await customerPage.goto('/login');
  // ... login as customer ...

  // 4. Customer scans QR (simulates app scan)
  // In real test, extract QR data from image and decode
  await customerPage.goto('/pay/merchant/42');
  await customerPage.fill('[data-testid="amount-input"]', '45000');
  await customerPage.click('[data-testid="confirm-payment-button"]');
  await customerPage.fill('[data-testid="pin-input"]', '654321');
  await customerPage.click('[data-testid="pay-button"]');

  // 5. Verify success on both sides
  await expect(customerPage.locator('[data-testid="payment-success"]')).toContainText('تم الدفع');
  
  // 6. Switch back to merchant — verify notification
  await page.bringToFront();
  await expect(page.locator('[data-testid="recent-transactions"]'))
    .toContainText('45,000');
});

// Merchant E2E Test: Payment Link Flow
test('merchant creates and shares payment link', async ({ page }) => {
  // 1. Login as merchant
  await page.goto('/merchant');
  await page.click('[data-testid="create-link-button"]');

  // 2. Fill link details
  await page.fill('[data-testid="amount-input"]', '45000');
  await page.fill('[data-testid="description-input"]', 'شنطة ظهر جلدية');
  await page.click('[data-testid="expiry-1h"]');
  await page.click('[data-testid="create-link-button"]');

  // 3. Verify link created
  await expect(page.locator('[data-testid="link-success"]')).toBeVisible();
  await expect(page.locator('[data-testid="link-url"]')).toContainText('pay.beza.com');

  // 4. Verify share buttons visible
  await expect(page.locator('[data-testid="share-whatsapp"]')).toBeVisible();
  await expect(page.locator('[data-testid="copy-link"]')).toBeVisible();
});
```
