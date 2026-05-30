# Government Collections Testing

## Test Strategy

### Unit Tests
```php
// Services
tests/Unit/Modules/GovernmentCollect/Services/
├── GovPaymentGatewayServiceTest.php
├── TaxPaymentServiceTest.php
├── FinePaymentServiceTest.php
├── TuitionPaymentServiceTest.php
├── PassportPaymentServiceTest.php
├── InvoiceServiceTest.php
├── ReconciliationServiceTest.php
├── ReceiptVerificationServiceTest.php

// Actions
tests/Unit/Modules/GovernmentCollect/Actions/
├── QueryTaxActionTest.php
├── PayTaxActionTest.php
├── QueryFineActionTest.php
├── PayFineActionTest.php
├── QueryTuitionActionTest.php
├── PayTuitionActionTest.php
├── GenerateReceiptActionTest.php
├── RunReconciliationActionTest.php

// Domain Models
tests/Unit/Modules/GovernmentCollect/Models/
├── GovernmentTransactionTest.php
├── GovernmentReceiptTest.php
├── GovernmentReconciliationTest.php

// Flutter
test/features/government/
├── domain/usecases/
├── data/repositories/
├── presentation/providers/
```

### Integration Tests

#### Ministry Adapter Integration
```php
// Mock ministry API responses, test adapters handle all scenarios
tests/Integration/Modules/GovernmentCollect/Integrations/
├── MinistryOfFinanceAdapterTest.php
├── MinistryOfInteriorAdapterTest.php
├── TrafficAuthorityAdapterTest.php
├── UniversityPortalAdapterTest.php
├── CentralGatewayAdapterTest.php

// Test scenarios per adapter:
// 1. Successful query — returns obligations
// 2. Reference not found — 404 response handled
// 3. Ministry timeout — retry logic
// 4. Authentication failure — token refresh
// 5. Malformed response — graceful error
// 6. Ministry down — queuing mode activated
```

#### Payment Flow Integration
```php
tests/Integration/Modules/GovernmentCollect/Flows/
├── TaxPaymentFlowTest.php
├── FinePaymentFlowTest.php
├── TuitionPaymentFlowTest.php
├── PassportPaymentFlowTest.php
├── GuestPaymentFlowTest.php
├── PaymentRetryFlowTest.php
```

### E2E Tests

#### API Contract Tests
```php
tests/E2E/Modules/GovernmentCollect/
├── TaxQueryAndPayTest.php
├── FineQueryAndPayWithDiscountTest.php
├── PassportPaymentAndReceiptTest.php
├── TuitionPaymentFromParentWalletTest.php
├── PaymentHistoryPaginationTest.php
├── ReceiptVerificationTest.php
├── ReconciliationRunTest.php
```

#### Mobile App E2E (Flutter Driver / Patrol)
```dart
test_e2e/features/government/
├── government_hub_test.dart
├── tax_payment_flow_test.dart
├── passport_payment_flow_test.dart
├── tuition_payment_flow_test.dart
├── receipt_view_and_share_test.dart
├── offline_receipt_test.dart
```

## Key Test Scenarios

### Tax Payment Flow
```php
class TaxPaymentFlowTest extends TestCase
{
    /** @test Complete happy path */
    public function test_successful_tax_query_and_payment(): void
    {
        // Given: User with 500,000 SYP wallet balance
        $user = User::factory()->create(['wallet_balance' => 500000]);
        $this->actingAs($user);

        // Given: MoF API returns tax obligation
        $this->mockMinistryOfFinance()
            ->shouldReceive('queryObligations')
            ->with('2536894751')
            ->andReturn(new QueryResult(
                obligations: [['year' => 2025, 'base' => 250000, 'penalty' => 12500]],
                totalDue: 262500,
            ));

        // When: User queries tax
        $response = $this->postJson('/api/v1/government/tax/query', [
            'tax_id' => '2536894751',
            'tax_type' => 'income',
        ]);

        // Then: Obligations returned
        $response->assertStatus(200);
        $response->assertJson(['data' => ['total_due' => 262500]]);

        // When: User pays
        $response = $this->withHeader('Idempotency-Key', 'uuid-123')
            ->postJson('/api/v1/government/tax/pay', [
                'tax_id' => '2536894751',
                'obligation_ids' => ['TAX-2025-001'],
                'total_amount' => 262500,
                'pin' => '1234',
            ]);

        // Then: Payment completed
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'amount_paid' => 263812,
                'payment_status' => 'completed',
            ]
        ]);

        // Then: User wallet debited
        $this->assertEquals(236188, $user->fresh()->wallet_balance);

        // Then: Transaction recorded
        $this->assertDatabaseHas('government_transactions', [
            'biller_reference' => '2536894751',
            'status' => 'completed',
            'receipt_ref' => $response->json('data.receipt_ref'),
        ]);
    }

    /** @test Ministry timeout — retry mechanism */
    public function test_ministry_timeout_retry(): void { /* ... */ }

    /** @test Duplicate payment prevented by idempotency key */
    public function test_idempotency_key_prevents_duplicate(): void { /* ... */ }

    /** @test Insufficient wallet balance rejects payment */
    public function test_insufficient_balance_rejected(): void { /* ... */ }

    /** @test Invalid tax ID returns error */
    public function test_invalid_tax_id(): void { /* ... */ }
}
```

### Receipt Verification
```php
class ReceiptVerificationTest extends TestCase
{
    /** @test Receipt QR verifies correctly */
    public function test_receipt_qr_verification(): void
    {
        $receipt = GovernmentReceipt::factory()->create();

        // QR data resolves to verification URL
        $response = $this->getJson($receipt->verification_url);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'receipt_ref' => $receipt->receipt_ref,
                'is_valid' => true,
                'amount_paid' => $receipt->total_paid,
                'biller_name_ar' => $receipt->biller_name_ar,
            ]
        ]);
    }

    /** @test Tampered receipt detected */
    public function test_tampered_receipt_detected(): void { /* ... */ }

    /** @test Revoked receipt shows invalid */
    public function test_revoked_receipt_shows_invalid(): void { /* ... */ }
}
```

## Test Data Factories
```php
// GovernmentTransaction factory
GovernmentTransaction::factory()->create([
    'service_type' => 'tax_income',
    'status' => 'completed',
    'amount' => 250000,
    'beza_fee' => 1250,
    'total_charged' => 251250,
]);

// Pre-built states
GovernmentTransaction::factory()->completed()->create();
GovernmentTransaction::factory()->failed()->create();
GovernmentTransaction::factory()->settled()->create();
GovernmentTransaction::factory()->pendingMinistry()->create();
```

## Load Test Scenarios (K6)
```javascript
// Simulate 1000 concurrent users paying taxes
export const options = {
    scenarios: {
        tax_query: { executor: 'ramping-vus', startVUs: 0, stages: [
            { duration: '1m', target: 500 },
            { duration: '3m', target: 500 },
            { duration: '1m', target: 0 },
        ]},
    },
    thresholds: {
        http_req_duration: ['p(95)<2000'],   // 95% under 2s
        'http_req_duration{type:query}': ['p(99)<5000'],
        'http_req_duration{type:pay}': ['p(99)<8000'],
    },
};
```
