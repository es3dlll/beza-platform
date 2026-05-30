# Testing Standards — Beza Platform

## Coverage Targets

| Layer | Framework | Test Type | Minimum Coverage |
|-------|-----------|-----------|-----------------|
| Backend | Laravel (PHPUnit) | Feature Tests | 80% |
| Backend | Laravel (PHPUnit) | Unit Tests | 70% |
| Mobile | Flutter (flutter_test) | Widget Tests | 60% |
| Mobile | Flutter (flutter_test) | Unit Tests | 70% |
| Admin | React (Vitest + Testing Library) | Component Tests | 60% |

Coverage measured per module. No single module may fall below the threshold. CI pipeline fails if coverage drops.

## Test Types Required

### Unit Tests
- **Services**: Test every method with all logical branches (if/else, switch, try/catch). Mock all dependencies (repositories, external services).
- **Repositories**: Test query logic with in-memory SQLite database. Verify correct model instantiation and collection shapes.
- **DTOs**: Test construction from arrays, request objects, and JSON. Test immutability. Test type coercion.
- **Validation Rules**: Test every rule with valid data, invalid data, boundary data, and null.
- **Events/DTOs**: Test serialization and deserialization. Event payload structure must match documented contract.

```php
// ✅ Unit test example — Service
test('it deducts balance when transfer is processed', function () {
    $wallet = Wallet::factory()->create(['balance' => 10000]);
    $repo = Mockery::mock(WalletRepository::class);
    $repo->shouldReceive('lockForUpdate')->andReturn($wallet);
    $repo->shouldReceive('updateBalance')->once();

    $service = new TransferService($repo, ...);
    $dto = new TransferRequestDto(senderId: $wallet->id, amount: 3000, ...);

    $result = $service->execute($dto);

    expect($result->senderNewBalance)->toBe(7000);
});
```

### Feature Tests (API)
- EVERY API endpoint requires a feature test.
- Each endpoint tested for at minimum: 200 (success), 400 (validation error), 401 (unauthenticated), 403 (unauthorized), 404 (not found), 422 (business rule failure), 500 (server error).
- Tests use model factories for setup.
- Tests assert on response structure, status code, and database state.

```php
// ✅ Feature test example
test('POST /api/v1/wallet/transfers — succeeds with valid data', function () {
    $sender = User::factory()->create();
    $wallet = Wallet::factory()->for($sender)->create(['balance' => 50000]);
    $receiverWallet = Wallet::factory()->create();

    $response = $this->actingAs($sender)->postJson('/api/v1/wallet/transfers', [
        'receiver_phone' => $receiverWallet->user->phone,
        'amount' => 10000,
        'currency' => 'SYP',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => ['transaction_id', 'sender_new_balance']]);
    $this->assertDatabaseHas('wallet_transactions', [
        'sender_wallet_id' => $wallet->id,
        'amount' => 10000,
        'status' => 'completed',
    ]);
});

test('POST /api/v1/wallet/transfers — fails with insufficient balance', function () {
    $sender = User::factory()->create();
    $wallet = Wallet::factory()->for($sender)->create(['balance' => 500]);

    $response = $this->actingAs($sender)->postJson('/api/v1/wallet/transfers', [
        'receiver_phone' => '+963900000001',
        'amount' => 10000,
        'currency' => 'SYP',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['error' => ['code' => 'INSUFFICIENT_BALANCE']]);
});
```

### Integration Tests
- Cross-module flows tested end-to-end through the backend.
- Critical flows: CFE posting → Ledger update → Wallet balance recalculation.
- Test that events are dispatched and listeners process correctly.
- Test that database transactions roll back properly on failure.
- Test that queued jobs are processed with correct data.

### End-to-End Tests
- Critical user journeys tested via Laravel Dusk (web) or integration test suites.
- Journeys to test: P2P transfer, cash-in at agent, cash-out at agent, account registration with KYC, currency exchange.
- E2E tests run against a staging environment with test data.
- E2E tests are not blocking for CI (run nightly), but smoke tests (happy path) must pass.

### Security Tests
- **Auth bypass**: Attempt all authenticated endpoints without token.
- **SQL injection**: Send malicious payloads to all string fields.
- **IDOR**: Try to access another user's wallet/transactions by changing ID in URL.
- **Rate limiting**: Send 100 requests in 1 second to verify 429 response.
- **Mass assignment**: Send unexpected fields in POST/PUT requests.
- **Syria-specific**: Test phone verification bypass, national ID spoofing, duplicate registration.

## Test Naming Convention

### PHP (Laravel)
```
test_{action}_{expected_result}
test_{scenario}_{expected_behavior}
```

Examples:
```
test_it_creates_a_wallet_when_user_registers
test_it_fails_when_insufficient_balance
test_it_blocks_suspicious_transfers_over_threshold
test_it_returns_401_when_unauthenticated
test_it_caches_wallet_balance_for_30_seconds
test_it_rolls_back_transaction_on_fx_service_failure
test_it_sends_notification_when_daily_limit_exceeded
```

### Flutter
```
{MethodName}_{Scenario}_{ExpectedResult}
```

Examples:
```
WalletService_getBalance_returnsWalletModel
TransferService_execute_throwsOnInsufficientBalance
LoginScreen_submit_validCredentials_navigatesToDashboard
WalletBalanceCard_displaysArabicFormatting
```

### React Admin
```
describe('ComponentName')
  it('scenario — expected behavior')
```

Examples:
```
describe('WalletTable')
  it('renders all wallets on success')
  it('shows loading skeleton while fetching')
  it('displays error state on API failure')
  it('filters wallets by status when dropdown changes')
```

## What MUST Be Tested

### Every API Endpoint
| Status Code | Scenario |
|-------------|----------|
| 200 | Successful request with expected response shape |
| 400 | Invalid input (missing fields, wrong types, format errors) |
| 401 | No authentication token or expired token |
| 403 | Authenticated but lacking permission |
| 404 | Resource does not exist |
| 422 | Business rule violation (insufficient balance, limit exceeded) |
| 500 | Internal server error (downstream failure, database timeout) |

### Every State Transition
- Wallet: active → frozen, frozen → active, active → closed.
- Transaction: pending → processing → completed, pending → failed, pending → cancelled.
- Agent: active → suspended, suspended → active.
- KYC: pending → verified, pending → rejected, verified → expired.

### Every Business Rule
- Balance sufficiency checks (transfer, cash-out).
- Daily/monthly transaction limits (per user, per wallet, per agent).
- Cumulative limits (total outflow in 24h, total inflow in 7d).
- Minimum/maximum transaction amounts.
- Age restrictions (minors cannot hold wallets without guardian).
- Duplicate detection (same receiver, same amount, same day).
- Fraud rules (velocity checks, geographic anomalies, amount patterns).

### Every Failure Scenario
- Downstream service timeout (FX rate API, SMS gateway, KYC provider).
- Database connection failure.
- Queue worker down.
- Third-party API returns unexpected response.
- Concurrent request race condition (test with `DB::transaction` + simultaneous requests).
- Network partition during multi-step transaction.
