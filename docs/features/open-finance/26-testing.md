# Open Finance Testing Strategy

## Test Pyramid
```
          ╱─────╲
        ╱  E2E   ╲         5 tests (developer journeys)
       ╱───────────╲
      ╱ Integration  ╲     25 tests (API + DB + Sandbox)
     ╱─────────────────╲
    ╱    Unit Tests      ╲   100+ tests (services, models, rules)
   ╱───────────────────────╲
```

## Unit Tests

### ApiKeyService Tests
```php
class ApiKeyServiceTest extends TestCase
{
    /** @test */
    public function it_creates_api_key_with_hash()
    {
        $developer = DeveloperAccount::factory()->create();
        $result = $this->apiKeyService->createKey($developer->id, new CreateKeyRequest(
            label: 'Test Key',
            environment: 'sandbox',
            scopes: ['payments.write', 'accounts.read'],
        ));

        $this->assertStringStartsWith('sk_test_', $result->getRawKey());
        $this->assertDatabaseHas('api_keys', [
            'developer_id' => $developer->id,
            'environment' => 'sandbox',
        ]);
    }

    /** @test */
    public function it_validates_key_successfully()
    {
        $key = $this->apiKeyService->createKey(...);
        $validated = $this->apiKeyService->validateKey($key->getRawKey());
        $this->assertNotNull($validated);
    }

    /** @test */
    public function it_rejects_expired_key()
    {
        $this->expectException(ApiKeyExpiredException::class);
        $key = $this->apiKeyService->createKey(...);
        // Travel to after expiry
        $this->travelTo(now()->addYear()->addDay());
        $this->apiKeyService->validateKey($key->getRawKey());
    }

    /** @test */
    public function it_rotates_key_correctly()
    {
        $oldKey = $this->apiKeyService->createKey(...);
        $newKey = $this->apiKeyService->rotateKey($oldKey->id, $developer->id);
        $this->assertNotEquals($oldKey->getRawKey(), $newKey->getRawKey());
    }
}
```

### RateLimitService Tests
```php
class RateLimitServiceTest extends TestCase
{
    /** @test */
    public function it_allows_requests_within_limit()
    {
        $service = $this->app->make(RateLimitService::class);
        for ($i = 0; $i < 10; $i++) {
            $service->checkRateLimit(1, 'free'); // 10/min limit
        }
        $this->assertTrue(true); // No exception
    }

    /** @test */
    public function it_blocks_requests_exceeding_limit()
    {
        $this->expectException(RateLimitExceededException::class);
        $service = $this->app->make(RateLimitService::class);
        for ($i = 0; $i < 12; $i++) {
            $service->checkRateLimit(1, 'free');
        }
    }
}
```

### WebhookDeliveryService Tests
```php
class WebhookDeliveryServiceTest extends TestCase
{
    /** @test */
    public function it_delivers_webhook_successfully()
    {
        Http::fake([
            'https://myshop.com/webhook' => Http::response('OK', 200),
        ]);
        
        $endpoint = WebhookEndpoint::factory()->create();
        $this->webhookService->sendWithRetry($endpoint, 'payment.completed', []);
        
        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_endpoint_id' => $endpoint->id,
            'status' => 'delivered',
            'response_code' => 200,
        ]);
    }

    /** @test */
    public function it_retries_on_failure()
    {
        Http::fake([
            'https://myshop.com/webhook' => Http::sequence()
                ->push('Error', 500)
                ->push('Error', 500)
                ->push('OK', 200),
        ]);
        
        $this->webhookService->sendWithRetry($endpoint, 'payment.completed', []);
        $this->assertDatabaseHas('webhook_deliveries', [
            'id' => $delivery->id,
            'attempts' => 3,
            'status' => 'delivered',
        ]);
    }

    /** @test */
    public function it_gives_up_after_max_retries()
    {
        Http::fake([
            'https://myshop.com/webhook' => Http::response('Error', 500),
        ]);
        
        $this->webhookService->sendWithRetry($endpoint, 'payment.completed', []);
        $this->assertDatabaseHas('webhook_deliveries', [
            'attempts' => 3,
            'status' => 'failed',
        ]);
    }
}
```

## Integration Tests

### API Tests
```php
class PaymentApiTest extends TestCase
{
    /** @test */
    public function authenticated_developer_can_initiate_payment()
    {
        $developer = DeveloperAccount::factory()->kycApproved()->create();
        $apiKey = ApiKey::factory()->forDeveloper($developer)->create();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey->raw_key,
            'Idempotency-Key' => Str::uuid(),
        ])->postJson('/api/v1/of/payments', [
            'amount' => 25000,
            'currency' => 'SYP',
            'recipient' => ['type' => 'wallet', 'phone' => '+963912345678'],
        ]);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'completed');
    }

    /** @test */
    public function unauthenticated_request_is_rejected()
    {
        $response = $this->postJson('/api/v1/of/payments', [
            'amount' => 25000,
            'currency' => 'SYP',
        ]);
        $response->assertStatus(401);
    }

    /** @test */
    public function rate_limited_request_returns_429()
    {
        $developer = DeveloperAccount::factory()->tier('free')->create();
        $apiKey = ApiKey::factory()->forDeveloper($developer)->create();
        
        for ($i = 0; $i < 10; $i++) {
            $this->withHeaders(['Authorization' => 'Bearer ' . $apiKey->raw_key])
                ->postJson(...);
        }
        
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $apiKey->raw_key])
            ->postJson(...);
        $response->assertStatus(429);
    }
}
```

## E2E Tests (Playwright)

```typescript
// Open Finance E2E: Developer Registration to First API Call
test('developer registers and makes first api call', async ({ page }) => {
  // 1. Visit developer portal
  await page.goto('https://developers.beza.com');
  
  // 2. Register
  await page.click('[data-testid="register-button"]');
  await page.fill('[data-testid="email"]', 'test@example.com');
  await page.fill('[data-testid="company"]', 'Test Company');
  await page.fill('[data-testid="phone"]', '+963900000001');
  await page.fill('[data-testid="password"]', 'SecurePass123!');
  await page.click('[data-testid="submit-registration"]');
  
  // 3. Verify sandbox key shown
  await expect(page.locator('[data-testid="sandbox-key"]')).toBeVisible();
  
  // 4. Open quick start guide
  await page.click('[data-testid="quick-start"]');
  
  // 5. Copy example cURL and make test call
  const apiKey = await page.locator('[data-testid="sandbox-key"]').textContent();
  
  // 6. Navigate to playground
  await page.click('[data-testid="playground-tab"]');
  await page.selectOption('[data-testid="method-select"]', 'POST');
  await page.fill('[data-testid="endpoint-input"]', '/v1/of/payments');
  await page.fill('[data-testid="body-editor"]', JSON.stringify({
    amount: 1000,
    currency: 'SYP',
    recipient: { type: 'wallet', phone: '+963900000001' },
  }));
  
  // 7. Send request
  await page.click('[data-testid="send-request"]');
  
  // 8. Verify response
  await expect(page.locator('[data-testid="response-status"]')).toContainText('201');
  await expect(page.locator('[data-testid="response-body"]')).toContainText('completed');
});
```
