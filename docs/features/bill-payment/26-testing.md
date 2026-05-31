# Bill Payment Testing Strategy

## Test Pyramid
```
          ╱─────╲
        ╱  E2E   ╲         5 tests (critical bill payment journeys)
       ╱───────────╲
      ╱ Integration  ╲     25 tests (API + DB + biller mock)
     ╱─────────────────╲
    ╱    Unit Tests      ╲   80+ tests (services, models, rules)
   ╱───────────────────────╲
```

## Unit Tests

### BillPaymentService Tests
```php
class BillPaymentServiceTest extends TestCase
{
    /** @test */
    public function it_fetches_bill_successfully()
    {
        $biller = Mockery::mock(BillerInterface::class);
        $biller->shouldReceive('fetchBill')
            ->with('123456789012345678901234')
            ->andReturn(new BillDTO(
                customerId: '123456789012345678901234',
                customerName: 'أحمد خالد',
                customerAddress: 'دمشق',
                billerType: 'peed',
                billerName: 'الشركة العامة للكهرباء',
                invoiceNumber: 'PE-2026-789012',
                billingPeriod: 'مايو 2026',
                amount: 42500,
                lateFee: 2125,
                totalDue: 44625,
                vat: null,
                dueDate: Carbon::parse('2026-06-15'),
                breakdown: [['label' => 'الاستهلاك', 'amount' => 40000]],
                billerReference: 'PE1234567890',
                paymentDate: null,
                isPaid: false,
            ));

        $this->billerProvider->shouldReceive('getBiller')
            ->with('peed')
            ->andReturn($biller);

        $result = $this->billPaymentService->fetchBill('peed', '123456789012345678901234');

        $this->assertEquals(44625, $result->totalDue);
        $this->assertEquals('أحمد خالد', $result->customerName);
    }

    /** @test */
    public function it_pays_bill_successfully()
    {
        // Mock biller fetch + confirm
        // Mock wallet balance check
        // Mock CFE hold/post
        // Assert transaction created with status=paid
        // Assert receipt generated
        // Assert events emitted
    }

    /** @test */
    public function it_fails_on_insufficient_balance()
    {
        $this->expectException(InsufficientBalanceException::class);
        // Setup: bill total 44,849, wallet balance 30,000
        // Execute pay
        // Assert: payment not sent to biller, hold released
    }

    /** @test */
    public function it_fails_on_bill_already_paid()
    {
        $this->expectException(BillAlreadyPaidException::class);
        // Setup: biller returns isPaid = true
    }

    /** @test */
    public function it_handles_biller_api_timeout()
    {
        $this->expectException(BillerConnectionException::class);
        // Setup: biller throws timeout exception
        // Assert: connection log recorded with success=false
    }

    /** @test */
    public function it_retries_on_biller_failure()
    {
        // Setup: first confirm fails, second succeeds
        // Assert: retry logic executed, final status = paid
    }

    /** @test */
    public function it_enforces_idempotency()
    {
        // Send same idempotency key twice → second returns existing txn
    }
}
```

### Fee Calculation Tests
```php
class BillFeeServiceTest extends TestCase
{
    /** @test */
    public function it_calculates_peed_fee()
    {
        $fee = $this->feeService->calculateBillPaymentFee(44625, 'peed');
        $this->assertEquals(224, $fee); // 0.5% = 223.125, ceil = 224
    }

    /** @test */
    public function it_calculates_syriatel_fee()
    {
        $fee = $this->feeService->calculateBillPaymentFee(33000, 'syriatel');
        $this->assertEquals(430, $fee); // 1% = 330 + 100 fixed = 430
    }

    /** @test */
    public function it_calculates_water_fee()
    {
        $fee = $this->feeService->calculateBillPaymentFee(8500, 'damascus_water');
        $this->assertEquals(64, $fee); // 0.75% = 63.75, ceil = 64
    }

    /** @test */
    public function it_calculates_government_fee()
    {
        $fee = $this->feeService->calculateBillPaymentFee(8000, 'government_fees');
        $this->assertEquals(120, $fee); // 1.5% = 120
    }
}
```

### Customer ID Validation Tests
```php
class BillValidationServiceTest extends TestCase
{
    /** @test */
    public function it_validates_peed_customer_id()
    {
        $valid = $this->validator->validate('peed', '123456789012345678901234');
        $this->assertTrue($valid);
    }

    /** @test */
    public function it_rejects_short_peed_id()
    {
        $valid = $this->validator->validate('peed', '12345678901234567890123');
        $this->assertFalse($valid);
    }

    /** @test */
    public function it_rejects_peed_id_with_letters()
    {
        $valid = $this->validator->validate('peed', '12345678901234567890123a');
        $this->assertFalse($valid);
    }

    /** @test */
    public function it_validates_syriatel_phone()
    {
        $valid = $this->validator->validate('syriatel', '0933123456');
        $this->assertTrue($valid);
    }

    /** @test */
    public function it_rejects_non_syriatel_prefix()
    {
        $valid = $this->validator->validate('syriatel', '0954123456');
        $this->assertFalse($valid); // 095x is MTN, not Syriatel
    }
}
```

### CsvBatchService Tests
```php
class CsvBatchServiceTest extends TestCase
{
    /** @test */
    public function it_parses_government_csv_correctly()
    {
        $csv = "national_id,fee_type,amount,reference,ministry\n1234567890123456,قيد فردي,5000,REF-123,العدل";
        $filePath = $this->createTempFile($csv);

        $batch = $this->csvBatchService->parseAndStore($filePath, 1);

        $this->assertDatabaseHas('csv_billable_items', [
            'customer_id' => '1234567890123456',
            'amount' => 5000,
            'reference' => 'REF-123',
        ]);
    }

    /** @test */
    public function it_notifies_matching_users()
    {
        // Create user with matching national ID
        // Process CSV
        // Assert notification sent
    }
}
```

### BillingScheduler Tests
```php
class BillingSchedulerTest extends TestCase
{
    /** @test */
    public function it_sends_reminders_for_due_bills()
    {
        // Create scheduled bill with reminder due today
        // Execute scheduler
        // Assert reminder notification sent
        // Assert last_reminded_at updated
    }

    /** @test */
    public function it_processes_auto_pay()
    {
        // Create scheduled bill with auto-pay due today
        // Mock successful payment
        // Execute scheduler
        // Assert payment made, schedule updated
    }

    /** @test */
    public function it_handles_auto_pay_insufficient_balance()
    {
        // Create scheduled bill with auto-pay due today
        // Mock insufficient balance
        // Execute scheduler
        // Assert failure recorded, retry scheduled
    }
}
```

## Integration Tests

### API Tests
```php
class BillPaymentApiTest extends TestCase
{
    /** @test */
    public function authenticated_user_can_fetch_bill()
    {
        $user = User::factory()->kycLevel(1)->create();
        $this->actingAs($user, 'sanctum');

        Biller::factory()->peed()->create();

        // Mock biller API response
        Http::fake(['https://api.peed.gov.sy/*' => Http::response([
            'customer_name' => 'أحمد خالد',
            'total_due' => 44625,
            // ... full response
        ])]);

        $response = $this->postJson('/api/v1/bills/fetch', [
            'customer_id' => '123456789012345678901234',
            'biller_type' => 'peed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_due', 44625)
            ->assertJsonPath('data.customer_name', 'أحمد خالد');
    }

    /** @test */
    public function unauthenticated_fetch_is_rejected()
    {
        $response = $this->postJson('/api/v1/bills/fetch', [
            'customer_id' => '123456789012345678901234',
            'biller_type' => 'peed',
        ]);
        $response->assertStatus(401);
    }

    /** @test */
    public function kyc0_user_cannot_fetch_bill()
    {
        $user = User::factory()->kycLevel(0)->create();
        $this->actingAs($user, 'sanctum');
        $response = $this->postJson('/api/v1/bills/fetch', []);
        $response->assertStatus(403);
    }

    /** @test */
    public function bill_history_returns_paginated_results()
    {
        $user = User::factory()->kycLevel(1)->create();
        $this->actingAs($user, 'sanctum');
        BillTransaction::factory()->count(25)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/bills/history?page=1&per_page=10');
        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.transactions')
            ->assertJsonPath('data.pagination.total', 25);
    }

    /** @test */
    public function user_can_set_bill_reminder()
    {
        $user = User::factory()->kycLevel(1)->create();
        $this->actingAs($user, 'sanctum');
        Biller::factory()->peed()->create();

        $response = $this->postJson('/api/v1/bills/reminder/set', [
            'biller_type' => 'peed',
            'customer_id' => '123456789012345678901234',
            'schedule_type' => 'monthly',
            'reminder_days' => 3,
            'next_due' => '2026-07-15',
            'auto_pay' => false,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('scheduled_bills', [
            'user_id' => $user->id,
            'customer_id' => '123456789012345678901234',
        ]);
    }
}
```

## E2E Tests (Playwright)

```typescript
// Bill Payment E2E Test: Pay Electricity Bill
test('user can pay electricity bill end-to-end', async ({ page }) => {
  // 1. Login
  await page.goto('/login');
  await page.fill('[data-testid="phone-input"]', '+96391234567');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="login-button"]');
  await page.waitForURL('/wallet');

  // 2. Navigate to bill payment
  await page.click('[data-testid="pay-bills-button"]');
  await page.waitForURL('/bills');

  // 3. Select electricity category → PEED
  await page.click('[data-testid="category-electricity"]');
  await page.click('[data-testid="biller-peed"]');

  // 4. Enter customer ID
  await page.fill('[data-testid="customer-id-input"]', '123456789012345678901234');
  await page.click('[data-testid="fetch-bill-button"]');

  // 5. Verify bill details
  await expect(page.locator('[data-testid="bill-amount"]')).toContainText('44,625');
  await expect(page.locator('[data-testid="customer-name"]')).toContainText('أحمد خالد');

  // 6. Confirm payment
  await page.click('[data-testid="confirm-payment-button"]');
  await page.fill('[data-testid="pin-input"]', '123456');
  await page.click('[data-testid="submit-payment-button"]');

  // 7. Verify success
  await expect(page.locator('[data-testid="success-message"]')).toContainText('تم الدفع بنجاح');
  await expect(page.locator('[data-testid="receipt-reference"]')).toBeVisible();
});

// E2E: Set Bill Reminder
test('user can set bill reminder', async ({ page }) => {
  // Login → navigate to bills → select biller
  // Enter customer ID → fetch bill
  // Tap "تعيين تذكير"
  // Select reminder days: 3
  // Toggle auto-pay off
  // Confirm
  // Assert: reminder set confirmation shown
  // Assert: scheduled bill appears in scheduled list
});
```
