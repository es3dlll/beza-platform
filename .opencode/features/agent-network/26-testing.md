# Agent Network Testing

## Unit Tests

### AgentService Tests
```php
class AgentServiceTest extends TestCase
{
    /** @test */
    public function it_registers_a_new_agent_with_pending_status()
    {
        $data = [
            'full_name' => 'محمد أحمد',
            'phone' => '+963933123456',
            'shop_name' => 'بقالة أبو محمد',
            'shop_type' => 'grocery',
            'address' => 'المزة، شارع الحمرا',
            'city' => 'دمشق',
            'district' => 'المزة',
            'location' => ['lat' => 33.5138, 'lng' => 36.2765],
        ];

        $agent = $this->agentService->register($data);

        $this->assertEquals('pending', $agent->status);
        $this->assertEquals('محمد أحمد', $agent->full_name);
        $this->assertNotNull($agent->code);
        $this->assertEquals('bronze', $agent->tier);
        $this->assertEquals(0, $agent->float_balance);
    }

    /** @test */
    public function it_rejects_duplicate_phone_number()
    {
        $this->expectException(DuplicateApplicationException::class);
        $this->expectExceptionMessage('رقم الهاتف مستخدم مسبقاً');

        // First registration
        $this->agentService->register(['phone' => '+963933123456', ...]);

        // Second registration with same phone
        $this->agentService->register(['phone' => '+963933123456', ...]);
    }

    /** @test */
    public function it_rejects_duplicate_national_id()
    {
        $this->expectException(DuplicateApplicationException::class);
        // Same logic as phone but for national_id
    }

    /** @test */
    public function it_checks_minimum_age_of_18()
    {
        $this->expectException(ValidationException::class);
        // Agent with age < 18 should be rejected
    }

    /** @test */
    public function it_approves_agent_when_kyc_is_complete()
    {
        $agent = $this->createPendingAgent();
        $approvedBy = 1; // Admin user ID

        $result = $this->agentService->approve($agent->id, $approvedBy);

        $this->assertEquals('active', $result->status);
        $this->assertEquals('active', $result->device->status);
        $this->assertNotNull($result->device->certificate);
    }

    /** @test */
    public function it_rejects_approval_when_documents_missing()
    {
        $this->expectException(IncompleteKycException::class);
        $agent = $this->createPendingAgent(['national_id_image' => null]);
        $this->agentService->approve($agent->id, 1);
    }

    /** @test */
    public function it_enforces_500m_minimum_distance_between_agents()
    {
        $this->expectException(AgentTooCloseException::class);
        // Create first agent at location (33.5138, 36.2765)
        // Try to create second agent at (33.5150, 36.2780) — only 250m away
    }

    /** @test */
    public function it_suspends_agent_with_proper_state_transition()
    {
        $agent = $this->createActiveAgent();
        $this->agentService->suspend($agent->id, 'float_discrepancy');

        $this->assertEquals('suspended', $agent->status);
        $this->assertEventDispatched(AgentSuspended::class);
    }

    /** @test */
    public function it_prevents_termination_from_pending_status()
    {
        $this->expectException(InvalidStateTransitionException::class);
        $agent = $this->createPendingAgent();
        $this->agentService->terminate($agent->id); // Should fail
    }
}
```

### CommissionService Tests
```php
class CommissionServiceTest extends TestCase
{
    /** @test */
    public function it_calculates_cash_in_commission_for_bronze_tier()
    {
        $commission = $this->commissionService->calculateCashInCommission(100000, AgentTier::BRONZE);
        $this->assertEquals(300, $commission->amount); // 0.3% of 100,000
    }

    /** @test */
    public function it_calculates_cash_in_commission_for_platinum_tier()
    {
        $commission = $this->commissionService->calculateCashInCommission(100000, AgentTier::PLATINUM);
        $this->assertEquals(600, $commission->amount); // 0.6% of 100,000
    }

    /** @test */
    public function it_enforces_minimum_commission_of_100_syp()
    {
        $commission = $this->commissionService->calculateCashInCommission(10000, AgentTier::BRONZE);
        $this->assertEquals(100, $commission->amount); // 0.3% of 10,000 = 30 → minimum 100 SYP
    }

    /** @test */
    public function it_calculates_cash_out_commission_for_silver_tier()
    {
        $commission = $this->commissionService->calculateCashOutCommission(50000, AgentTier::SILVER);
        $this->assertEquals(300, $commission->amount); // 0.6% of 50,000
    }

    /** @test */
    public function it_accrues_commission_and_updates_pending_balance()
    {
        $agent = $this->createActiveAgent(['pending_commission' => 0]);
        $amount = new Money(500, Currency::SYP);

        $this->commissionService->accrueCommission($agent->id, $amount, 'CI-12345');

        $this->assertEquals(500, $agent->pending_commission);
        $this->assertDatabaseHas('agent_commissions', [
            'agent_id' => $agent->id,
            'amount' => 500,
            'status' => 'accrued',
        ]);
    }

    /** @test */
    public function it_settles_all_accrued_commissions_in_batch()
    {
        // Create agents with pending commissions
        $agent1 = $this->createActiveAgent(['pending_commission' => 12500]);
        $agent2 = $this->createActiveAgent(['pending_commission' => 23400]);

        $settlement = $this->commissionService->settleDaily();

        $this->assertEquals('completed', $settlement->status);
        $this->assertEquals(35900, $settlement->total_amount);
        $this->assertEquals(0, $agent1->pending_commission);
        $this->assertEquals(0, $agent2->pending_commission);
    }

    /** @test */
    public function it_handles_settlement_when_agent_has_no_wallet()
    {
        $agent = $this->createActiveAgent(['pending_commission' => 5000, 'user_id' => null]);

        $settlement = $this->commissionService->settleDaily();

        // Agent should be skipped, not crash
        $this->assertEquals('completed', $settlement->status);
        $agent->refresh();
        $this->assertEquals(5000, $agent->pending_commission); // Not settled
    }
}
```

### FloatService Tests
```php
class FloatServiceTest extends TestCase
{
    /** @test */
    public function it_returns_current_float_balance()
    {
        $agent = $this->createActiveAgent(['float_balance' => 1000000]);
        $balance = $this->floatService->getBalance($agent->id);
        $this->assertEquals(1000000, $balance->amount);
    }

    /** @test */
    public function it_verifies_sufficient_float()
    {
        $agent = $this->createActiveAgent(['float_balance' => 1000000]);
        $this->assertTrue($this->floatService->canDebit($agent->id, new Money(500000, Currency::SYP)));
        $this->assertFalse($this->floatService->canDebit($agent->id, new Money(2000000, Currency::SYP)));
    }

    /** @test */
    public function it_debits_float_and_records_movement()
    {
        $agent = $this->createActiveAgent(['float_balance' => 1000000]);
        $this->floatService->debit($agent->id, new Money(200000, Currency::SYP), 'cash_in');

        $this->assertEquals(800000, $agent->float_balance);
        $this->assertDatabaseHas('agent_transactions', [
            'agent_id' => $agent->id,
            'type' => 'cash_in',
            'amount' => 200000,
            'balance_before' => 1000000,
            'balance_after' => 800000,
        ]);
    }

    /** @test */
    public function it_throws_on_insufficient_float_debit()
    {
        $this->expectException(InsufficientFloatException::class);
        $agent = $this->createActiveAgent(['float_balance' => 100000]);
        $this->floatService->debit($agent->id, new Money(200000, Currency::SYP), 'cash_in');
    }

    /** @test */
    public function it_credits_float_and_records_movement()
    {
        $agent = $this->createActiveAgent(['float_balance' => 800000]);
        $this->floatService->credit($agent->id, new Money(200000, Currency::SYP), 'cash_out');

        $this->assertEquals(1000000, $agent->float_balance);
    }

    /** @test */
    public function it_processes_wallet_top_up()
    {
        $agent = $this->createActiveAgent(['float_balance' => 50000]);
        $this->floatService->topUp($agent->id, new Money(500000, Currency::SYP), FloatFundingSource::WALLET);

        $this->assertEquals(550000, $agent->float_balance);
    }

    /** @test */
    public function it_enforces_max_float_balance_for_tier()
    {
        $this->expectException(MaxFloatExceededException::class);
        $agent = $this->createActiveAgent(['tier' => 'bronze', 'float_balance' => 4800000]);
        $this->floatService->topUp($agent->id, new Money(500000, Currency::SYP), FloatFundingSource::WALLET);
        // 4.8M + 0.5M = 5.3M > Bronze max 5M
    }
}
```

## Integration Tests

### CashIn API Tests
```php
class CashInApiTest extends TestCase
{
    /** @test */
    public function it_completes_cash_in_successfully()
    {
        $agent = $this->createActiveAgent(['float_balance' => 1000000]);
        $customer = $this->createCustomer(['wallet_balance' => 150000]);
        $this->createVerification($customer->phone, '4821');

        $response = $this->actingAsAgent($agent)->postJson('/api/v1/agent/cash-in', [
            'verification_id' => $this->verification->id,
            'verification_code' => '4821',
            'customer_phone' => $customer->phone,
            'amount' => 100000,
            'idempotency_key' => Str::uuid(),
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.amount', 100000);
        $response->assertJsonPath('data.commission_earned', 500);
        $response->assertJsonPath('data.agent_float_after', 900000);
        $this->assertEquals(250000, $customer->wallet->balance);
    }

    /** @test */
    public function it_rejects_cash_in_with_insufficient_float()
    {
        $agent = $this->createActiveAgent(['float_balance' => 50000]);
        $customer = $this->createCustomer();

        $response = $this->actingAsAgent($agent)->postJson('/api/v1/agent/cash-in', [
            'amount' => 100000,
            ...
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'INSUFFICIENT_FLOAT');
    }

    /** @test */
    public function it_rejects_duplicate_idempotency_key()
    {
        // First request: success
        // Second request with same key: 409 Conflict
        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'DUPLICATE_REQUEST');
    }

    /** @test */
    public function it_rejects_invalid_verification_code()
    {
        $response = $this->postJson('/api/v1/agent/cash-in', [
            'verification_code' => '0000',
            ...
        ]);
        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'INVALID_VERIFICATION_CODE');
    }

    /** @test */
    public function it_enforces_daily_limit()
    {
        // Agent with daily cash-in already at 4.8M of 5M limit
        // Try to cash-in 500K → should fail (would exceed limit)
        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'DAILY_LIMIT_EXCEEDED');
    }
}
```

### CashOut API Tests
```php
class CashOutApiTest extends TestCase
{
    /** @test */
    public function it_completes_cash_out_successfully()
    {
        $agent = $this->createActiveAgent(['float_balance' => 900000]);
        $customer = $this->createCustomer(['wallet_balance' => 250000]);

        $response = $this->actingAsAgent($agent)->postJson('/api/v1/agent/cash-out', [
            'amount' => 50000,
            'customer_pin' => '1234',
            ...
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.amount', 50000);
        $response->assertJsonPath('data.fee', 750);
        $response->assertJsonPath('data.agent_float_after', 950000);
        $this->assertEquals(199250, $customer->wallet->balance);
    }

    /** @test */
    public function it_rejects_cash_out_with_insufficient_customer_balance()
    {
        $customer = $this->createCustomer(['wallet_balance' => 10000]);
        // Tries to cash out 50000
        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'INSUFFICIENT_BALANCE');
    }

    /** @test */
    public function it_requires_biometric_for_high_value()
    {
        // Cash-out > 500K SYP with biometric_verified = false
        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'BIOMETRIC_REQUIRED');
    }
}
```

## E2E Tests (Playwright)

```dart
// Full cash-in flow on POS app (Flutter integration test)

test('Agent completes cash-in successfully', () async {
  // 1. Launch POS app
  await app.launch();

  // 2. Login as agent
  await app.enterText('phoneInput', '+963933123456');
  await app.enterText('pinInput', '123456');
  await app.tap('loginButton');
  await app.waitForText('وكيل Beza');

  // 3. Verify float displayed
  await app.waitForText('1,000,000');

  // 4. Tap cash-in button
  await app.tap('cashInButton');

  // 5. Enter customer phone
  await app.tap('keypad_0');
  await app.tap('keypad_9');
  await app.tap('keypad_6');
  // ... enter full phone number
  await app.tap('nextButton');

  // 6. Enter verification code
  await app.tap('keypad_4');
  await app.tap('keypad_8');
  await app.tap('keypad_2');
  await app.tap('keypad_1');
  // Auto-advance to next step

  // 7. Enter amount
  await app.tap('keypad_1');
  await app.tap('keypad_0');
  await app.tap('keypad_0');
  await app.tap('keypad_0');
  await app.tap('keypad_0');
  await app.tap('keypad_0'); // 100,000
  await app.tap('nextButton');

  // 8. Confirm
  await app.waitForText('100,000');
  await app.waitForText('500'); // estimated commission
  await app.tap('confirmButton');

  // 9. Verify success
  await app.waitForText('تم الإيداع بنجاح');
  await app.waitForText('100,000 ل.س');

  // 10. Print receipt
  await app.tap('printButton');
  await app.waitForText('تمت الطباعة');

  // 11. Verify float updated on home
  await app.tap('homeButton');
  await app.waitForText('900,000'); // 1M - 100K
});
```

## Load Tests

### k6 Load Test Script
```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '2m', target: 50 },  // Ramp up to 50 concurrent agents
    { duration: '5m', target: 100 }, // Maintain 100 concurrent
    { duration: '2m', target: 0 },   // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% of requests under 2s
    http_req_failed: ['rate<0.01'],    // <1% failure rate
  },
};

const BASE_URL = 'https://agent-api.beza.com/api/v1';

export default function () {
  // Login
  const loginRes = http.post(`${BASE_URL}/agent/login`, {
    phone: `+963933${String(100000 + __VU).slice(1)}`,
    pin: '123456',
    device_id: `DEVICE-LOAD-${__VU}`,
  });
  check(loginRes, { 'login succeeded': (r) => r.status === 200 });
  const token = loginRes.json('data.token');

  const headers = {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  };

  // Cash-in
  const customerPhone = `+963912${String(100000 + __VU * 2).slice(1)}`;
  const cashInRes = http.post(
    `${BASE_URL}/agent/cash-in`,
    JSON.stringify({
      verification_id: `ver_load_${__VU}`,
      verification_code: '1111',
      customer_phone: customerPhone,
      amount: 100000,
      idempotency_key: `load_${__VU}_${Date.now()}`,
    }),
    { headers }
  );
  check(cashInRes, { 'cash-in succeeded': (r) => r.status === 200 });

  // Check float
  const floatRes = http.get(`${BASE_URL}/agent/float`, { headers });
  check(floatRes, { 'float retrieved': (r) => r.status === 200 });

  sleep(1);
}
```

## Test Coverage Targets
| Module | Unit Test Coverage | Integration Coverage |
|--------|-------------------|---------------------|
| AgentService | 95% | 90% |
| CommissionService | 95% | 90% |
| FloatService | 95% | 90% |
| CashInService | 90% | 95% |
| CashOutService | 90% | 95% |
| CustomerVerificationService | 90% | 85% |
| LimitService | 95% | 85% |
| AgentController | — | 95% (all endpoints) |
| CashInController | — | 95% |
| CashOutController | — | 95% |
| Flutter Screens (widget test) | — | 90% (all states) |
