# Open Finance Backend Architecture

## Module Structure (Laravel)
```
app/Modules/OpenFinance/
├── Controllers/
│   ├── AuthController.php               # OAuth 2.0 token endpoints
│   ├── ApiKeyController.php             # API key management
│   ├── PaymentController.php            # Payment initiation
│   ├── AccountController.php            # Account information
│   ├── WalletController.php             # Wallet management
│   ├── TransactionController.php        # Transaction queries
│   ├── AgentController.php              # Agent locator
│   ├── FxRateController.php             # FX rate queries
│   ├── WebhookController.php            # Webhook config
│   ├── SandboxController.php            # Sandbox management
│   └── ConsoleController.php            # Developer portal API
│
├── Actions/
│   ├── RegisterDeveloperAction.php      # Developer account creation
│   ├── CreateApiKeyAction.php           # API key generation
│   ├── RotateApiKeyAction.php           # Key rotation
│   ├── RevokeApiKeyAction.php           # Key revocation
│   ├── IssueOAuthTokenAction.php        # OAuth token issuance
│   ├── RefreshOAuthTokenAction.php      # Token refresh
│   ├── InitiatePaymentAction.php        # Payment orchestration
│   ├── InitiateBulkPaymentAction.php    # Batch payments
│   ├── ProcessWebhookDeliveryAction.php # Webhook send + retry
│   ├── ResetSandboxAction.php           # Sandbox data reset
│   └── LogApiUsageAction.php            # Usage tracking
│
├── Services/
│   ├── ApiKeyService.php                # Key generation, hashing, validation
│   ├── OAuthService.php                 # Token issuance, validation, refresh
│   ├── RateLimitService.php             # Tier-based rate limiting
│   ├── WebhookDeliveryService.php       # Delivery, retry, logging
│   ├── DeveloperPortalService.php       # Portal data aggregation
│   ├── SandboxService.php               # Sandbox simulation engine
│   ├── ApiVersioningService.php         # Version routing + migration
│   ├── SignatureService.php             # HMAC payload signing
│   └── IdempotencyService.php           # Idempotency key tracking
│
├── Repositories/
│   ├── DeveloperAccountRepository.php
│   ├── ApiKeyRepository.php
│   ├── OAuthClientRepository.php
│   ├── OAuthTokenRepository.php
│   ├── WebhookEndpointRepository.php
│   ├── WebhookDeliveryRepository.php
│   └── ApiUsageLogRepository.php
│
├── Models/
│   ├── DeveloperAccount.php
│   ├── ApiKey.php
│   ├── OAuthClient.php
│   ├── OAuthToken.php
│   ├── WebhookEndpoint.php
│   ├── WebhookDelivery.php
│   └── ApiUsageLog.php
│
├── Policies/
│   ├── ApiKeyPolicy.php                 # Owner-only operations
│   ├── WebhookPolicy.php                # Owner-only config
│   └── DeveloperPolicy.php              # Account management
│
├── Events/
│   ├── DeveloperRegistered.php
│   ├── ApiKeyCreated.php
│   ├── ApiKeyRevoked.php
│   ├── WebhookDelivered.php
│   ├── WebhookDeliveryFailed.php
│   └── ApiUsageLimitReached.php
│
├── Jobs/
│   ├── ProcessWebhookDeliveryJob.php    # Async webhook delivery
│   ├── RotateExpiredKeysJob.php         # Scheduled key rotation
│   ├── CalculateDeveloperUsageJob.php   # Daily usage aggregation
│   └── SendUsageAlertsJob.php           # Threshold alerts
│
├── Listeners/
│   ├── LogWebhookDelivery.php
│   ├── NotifyDeveloperOnKeyEvent.php
│   └── UpdateDeveloperUsageStats.php
│
├── Rules/
│   ├── ValidApiKeyScope.php
│   ├── ValidWebhookUrl.php
│   └── WithinSandboxLimits.php
│
├── Enums/
│   ├── ApiKeyScope.php                  # payments.write, accounts.read, etc.
│   ├── ApiKeyEnvironment.php            # sandbox, production
│   ├── OAuthGrantType.php               # client_credentials, authorization_code
│   ├── WebhookEvent.php                 # payment.completed, etc.
│   ├── WebhookDeliveryStatus.php        # pending, delivered, failed
│   └── DeveloperTier.php                # free, startup, business, enterprise
│
├── Exceptions/
│   ├── InvalidApiKeyException.php
│   ├── RateLimitExceededException.php
│   ├── InvalidSignatureException.php
│   ├── IdempotencyConflictException.php
│   └── SandboxOperationException.php
│
├── Providers/
│   └── OpenFinanceServiceProvider.php
│
└── routes/
    ├── api.php                          # OF API routes
    └── portal-api.php                   # Console internal API
```

## Service Layer Detail

### ApiKeyService
```php
class ApiKeyService
{
    public function __construct(
        private ApiKeyRepository $keyRepo,
        private DeveloperAccountRepository $devRepo,
    ) {}

    public function createKey(int $developerId, CreateKeyRequest $request): ApiKey
    {
        $keyPrefix = $request->environment === 'sandbox' ? 'sk_test_' : 'sk_live_';
        $rawKey = $keyPrefix . Str::random(48);
        $hashedKey = hash('sha256', $rawKey);

        $key = $this->keyRepo->create([
            'developer_id' => $developerId,
            'label' => $request->label,
            'key_prefix' => substr($rawKey, 0, 12) . '...',
            'key_hash' => $hashedKey,
            'environment' => $request->environment,
            'scopes' => json_encode($request->scopes),
            'expires_at' => $request->environment === 'sandbox' ? null : now()->addYear(),
        ]);

        // Return raw key once — never stored again
        return $key->setRawKey($rawKey);
    }

    public function validateKey(string $rawKey): ?ApiKey
    {
        $hashed = hash('sha256', $rawKey);
        $key = $this->keyRepo->findByHash($hashed);
        if (!$key || $key->isExpired() || $key->isRevoked()) {
            return null;
        }
        $key->touchLastUsed();
        return $key;
    }

    public function revokeKey(int $keyId, int $developerId): void
    {
        $key = $this->keyRepo->findOrFail($keyId);
        throw_if($key->developer_id !== $developerId, AuthorizationException::class);
        $this->keyRepo->update($keyId, ['revoked_at' => now()]);
    }

    public function rotateKey(int $keyId, int $developerId): ApiKey
    {
        $oldKey = $this->keyRepo->findOrFail($keyId);
        throw_if($oldKey->developer_id !== $developerId, AuthorizationException::class);
        // Expire old key in 24h
        $this->keyRepo->update($keyId, ['expires_at' => now()->addHours(24)]);
        // Create new key with same params
        return $this->createKey($developerId, new CreateKeyRequest(
            label: $oldKey->label,
            environment: $oldKey->environment,
            scopes: json_decode($oldKey->scopes, true),
        ));
    }
}
```

### OAuthService
```php
class OAuthService
{
    public function __construct(
        private OAuthClientRepository $clientRepo,
        private OAuthTokenRepository $tokenRepo,
        private DeveloperAccountRepository $devRepo,
    ) {}

    public function issueClientCredentialsToken(string $clientId, string $clientSecret): OAuthToken
    {
        $client = $this->clientRepo->findByClientId($clientId);
        throw_unless($client, new InvalidClientException());
        throw_unless(
            hash_equals($client->client_secret, hash('sha256', $clientSecret)),
            new InvalidClientException()
        );

        $token = Str::random(64);
        $this->tokenRepo->create([
            'oauth_client_id' => $client->id,
            'token_hash' => hash('sha256', $token),
            'scopes' => $client->default_scopes,
            'expires_at' => now()->addHours(2),
            'access_token' => $token,
        ]);

        return new OAuthToken(
            accessToken: $token,
            tokenType: 'Bearer',
            expiresIn: 7200,
            scope: $client->default_scopes,
        );
    }

    public function validateToken(string $token): ?DeveloperAccount
    {
        $hashed = hash('sha256', $token);
        $tokenRecord = $this->tokenRepo->findValidByHash($hashed);
        if (!$tokenRecord || $tokenRecord->isExpired()) {
            return null;
        }
        $client = $this->clientRepo->find($tokenRecord->oauth_client_id);
        return $this->devRepo->find($client->developer_id);
    }
}
```

### RateLimitService
```php
class RateLimitService
{
    private array $tierLimits = [
        'free' => ['requests_per_minute' => 10, 'requests_per_day' => 1000],
        'startup' => ['requests_per_minute' => 100, 'requests_per_day' => 10000],
        'business' => ['requests_per_minute' => 500, 'requests_per_day' => 100000],
        'enterprise' => ['requests_per_minute' => 2000, 'requests_per_day' => 1000000],
    ];

    public function __construct(private Cache $cache) {}

    public function checkRateLimit(int $developerId, string $tier): void
    {
        $limits = $this->tierLimits[$tier] ?? $this->tierLimits['free'];
        $minKey = "ratelimit:{$developerId}:minute";
        $dayKey = "ratelimit:{$developerId}:day";

        $minuteCount = (int) $this->cache->increment($minKey);
        if ($minuteCount === 1) {
            $this->cache->expire($minKey, 60);
        }

        $dayCount = (int) $this->cache->increment($dayKey);
        if ($dayCount === 1) {
            $this->cache->expire($dayKey, 86400);
        }

        throw_if(
            $minuteCount > $limits['requests_per_minute'],
            new RateLimitExceededException('minute', $limits['requests_per_minute'])
        );
        throw_if(
            $dayCount > $limits['requests_per_day'],
            new RateLimitExceededException('day', $limits['requests_per_day'])
        );
    }
}
```

### WebhookDeliveryService
```php
class WebhookDeliveryService
{
    public function __construct(
        private WebhookEndpointRepository $endpointRepo,
        private WebhookDeliveryRepository $deliveryRepo,
        private Http $http,
    ) {}

    public function deliver(string $eventType, array $payload, int $developerId): void
    {
        $endpoints = $this->endpointRepo->findByDeveloperAndEvent($developerId, $eventType);
        foreach ($endpoints as $endpoint) {
            dispatch(new ProcessWebhookDeliveryJob($endpoint, $eventType, $payload));
        }
    }

    public function sendWithRetry(WebhookEndpoint $endpoint, string $eventType, array $payload): void
    {
        $signature = $this->signPayload($payload, $endpoint->signing_secret);
        $delivery = $this->deliveryRepo->create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => $eventType,
            'payload' => json_encode($payload),
            'status' => WebhookDeliveryStatus::PENDING,
        ]);

        try {
            $response = $this->http->post($endpoint->url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Beza-Signature' => $signature,
                    'X-Beza-Event' => $eventType,
                    'X-Beza-Delivery-Id' => $delivery->id,
                ],
                'json' => $payload,
                'timeout' => 10,
            ]);

            $this->deliveryRepo->update($delivery->id, [
                'status' => WebhookDeliveryStatus::DELIVERED,
                'response_code' => $response->status(),
                'response_body' => $response->body(),
                'delivered_at' => now(),
            ]);
        } catch (Exception $e) {
            $attempts = $delivery->attempts + 1;
            $this->deliveryRepo->update($delivery->id, [
                'status' => $attempts >= 3 ? WebhookDeliveryStatus::FAILED : WebhookDeliveryStatus::PENDING,
                'attempts' => $attempts,
                'last_error' => $e->getMessage(),
            ]);
            if ($attempts < 3) {
                dispatch(new ProcessWebhookDeliveryJob($endpoint, $eventType, $payload))
                    ->delay(now()->addMinutes(pow(2, $attempts)));
            }
        }
    }

    private function signPayload(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}
```

### DeveloperPortalService
```php
class DeveloperPortalService
{
    public function getDashboardStats(int $developerId): array
    {
        $today = now()->startOfDay();
        return [
            'daily_requests' => $this->usageRepo->countByDeveloperAndDate($developerId, $today),
            'error_rate' => $this->usageRepo->errorRateByDeveloperAndDate($developerId, $today),
            'p99_latency' => $this->usageRepo->p99LatencyByDeveloperAndDate($developerId, $today),
            'active_apps' => $this->keyRepo->countActiveByDeveloper($developerId),
            'time_series' => $this->usageRepo->hourlyBreakdown($developerId, $today),
            'recent_requests' => $this->usageRepo->recentByDeveloper($developerId, 10),
            'service_status' => $this->getServiceStatus(),
        ];
    }
}
```

### SandboxService
```php
class SandboxService
{
    private array $simulatedBalances = [];
    private array $simulatedTransactions = [];

    public function reset(int $developerId): void
    {
        Cache::forget("sandbox:{$developerId}:*");
        // Re-initialize simulated data
        $this->simulatedBalances[$developerId] = [
            'SYP' => 1000000,
            'USD' => 10000,
        ];
        $this->simulatedTransactions[$developerId] = [];
    }

    public function processPayment(int $developerId, array $request): array
    {
        $amount = $request['amount'];
        $currency = $request['currency'];
        $balance = &$this->simulatedBalances[$developerId][$currency];
        throw_if($balance < $amount, new InsufficientBalanceException());

        $balance -= $amount;
        $transaction = [
            'id' => 'txn_sandbox_' . Str::random(12),
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'completed',
            'created_at' => now()->toIso8601String(),
        ];
        $this->simulatedTransactions[$developerId][] = $transaction;
        return $transaction;
    }
}
```

### ApiVersioningService
```php
class ApiVersioningService
{
    private array $versions = [
        'v1' => ['status' => 'active', 'supported_until' => null],
        'v2' => ['status' => 'deprecated', 'supported_until' => '2027-06-01'],
    ];

    public function resolveVersion(string $acceptHeader): string
    {
        preg_match('/application\/vnd\.beza\.(v\d+)\+json/', $acceptHeader, $matches);
        $version = $matches[1] ?? 'v1';
        throw_unless(
            isset($this->versions[$version]),
            new UnsupportedVersionException($version)
        );
        return $version;
    }

    public function getVersionStatus(string $version): array
    {
        return $this->versions[$version] ?? ['status' => 'unknown'];
    }
}
```

## API Endpoints
```php
// Open Finance Routes (prefix: /api/v1/of)

Route::prefix('of')->group(function () {
    // Public — no auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/oauth/token', [AuthController::class, 'token']);
    Route::get('/fx-rates', [FxRateController::class, 'latest']);
    Route::get('/fx-rates/historical', [FxRateController::class, 'historical']);

    // Authenticated — API key or OAuth
    Route::middleware(['auth:api-key,oauth'])->group(function () {
        Route::get('/accounts/balance', [AccountController::class, 'balance']);
        Route::get('/accounts/transactions', [AccountController::class, 'transactions']);

        Route::post('/payments', [PaymentController::class, 'initiate']);
        Route::post('/payments/bulk', [PaymentController::class, 'bulk']);
        Route::get('/payments/{id}', [PaymentController::class, 'status']);

        Route::get('/wallets', [WalletController::class, 'list']);
        Route::post('/wallets', [WalletController::class, 'create']);
        Route::post('/wallets/fund', [WalletController::class, 'fund']);

        Route::get('/transactions', [TransactionController::class, 'list']);
        Route::get('/transactions/{id}', [TransactionController::class, 'detail']);
        Route::get('/transactions/export', [TransactionController::class, 'export']);

        Route::get('/agents', [AgentController::class, 'nearby']);
        Route::get('/agents/{id}', [AgentController::class, 'detail']);

        Route::get('/fx-rates/live', [FxRateController::class, 'live']);
    });

    // Console routes (developer portal internal)
    Route::middleware(['auth:sanctum'])->prefix('console')->group(function () {
        Route::get('/dashboard', [ConsoleController::class, 'dashboard']);
        Route::post('/api-keys', [ApiKeyController::class, 'create']);
        Route::get('/api-keys', [ApiKeyController::class, 'list']);
        Route::post('/api-keys/{id}/rotate', [ApiKeyController::class, 'rotate']);
        Route::delete('/api-keys/{id}', [ApiKeyController::class, 'revoke']);

        Route::post('/webhooks', [WebhookController::class, 'create']);
        Route::get('/webhooks', [WebhookController::class, 'list']);
        Route::put('/webhooks/{id}', [WebhookController::class, 'update']);
        Route::delete('/webhooks/{id}', [WebhookController::class, 'delete']);
        Route::get('/webhooks/{id}/deliveries', [WebhookController::class, 'deliveries']);
        Route::post('/webhooks/{id}/test', [WebhookController::class, 'test']);
        Route::post('/webhooks/deliveries/{id}/retry', [WebhookController::class, 'retry']);

        Route::post('/sandbox/reset', [SandboxController::class, 'reset']);
        Route::get('/sandbox/accounts', [SandboxController::class, 'accounts']);
    });
});
```
