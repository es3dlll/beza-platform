# Open Finance Security

## Authentication & Authorization

### API Key Security
```php
// API Key generation
public function generateApiKey(string $prefix = 'sk_live_'): string
{
    $random = bin2hex(random_bytes(32));
    return $prefix . $random;  // 70 characters total
}

// API Key hashing (one-way, never stored plaintext)
public function hashKey(string $rawKey): string
{
    return hash('sha256', $rawKey);
}

// Key validation on every request
public function authenticateRequest(string $authHeader): DeveloperAccount
{
    // Expect: "Bearer sk_live_abc123..."
    $rawKey = Str::after($authHeader, 'Bearer ');
    $hash = hash('sha256', $rawKey);
    
    $apiKey = $this->keyRepo->findByHash($hash);
    throw_unless($apiKey, new InvalidApiKeyException());
    throw_if($apiKey->isExpired(), new ApiKeyExpiredException());
    throw_if($apiKey->isRevoked(), new ApiKeyRevokedException());
    
    $apiKey->touchLastUsed();
    return $apiKey->developer;
}
```

### OAuth 2.0 Token Security
```php
// Token generation
public function generateAccessToken(): string
{
    return 'beza_oat_' . bin2hex(random_bytes(32));
}

// Token validation middleware
public function validateOAuthToken(string $token): DeveloperAccount
{
    $hash = hash('sha256', $token);
    $tokenRecord = Cache::remember("oauth:{$hash}", 600, function () use ($hash) {
        return OAuthToken::where('token_hash', $hash)
            ->where('expires_at', '>', now())
            ->whereNull('revoked_at')
            ->first();
    });
    
    throw_unless($tokenRecord, new InvalidTokenException());
    return $tokenRecord->client->developer;
}
```

### Scope Enforcement
```php
// Middleware checks requested endpoint against key scopes
public function enforceScope(string $requiredScope, ApiKey $key): void
{
    $scopeMap = [
        'payments.write' => 'payments.write',
        'payments.read' => 'payments.read',
        'accounts.read' => 'accounts.read',
        'wallets.write' => 'wallets.write',
        'wallets.read' => 'wallets.read',
        'transactions.read' => 'transactions.read',
        'webhooks.read' => 'webhooks.read',
        'webhooks.write' => 'webhooks.write',
    ];
    
    $scope = $scopeMap[$requiredScope] ?? null;
    throw_unless($scope && in_array($scope, $key->scopes), 
        new InsufficientScopeException($requiredScope));
}
```

## Sensitive Operations
```
Operations requiring additional verification:
  - Production API key creation (requires KYC approval)
  - API key rotation (old key kept valid for 24h)
  - Webhook URL change (test event sent before activation)
  - Developer account email change (email verification required)
  - Tier downgrade (confirmation dialog, effects explained)
```

## Data Protection

### Encryption at Rest
```php
// API key hash: SHA-256 (one-way)
// OAuth client secret: SHA-256 (one-way)
// Webhook signing secret: SHA-256 (one-way)
// Developer passwords: bcrypt cost 12
// API usage logs: no PII stored in logs
// Sandbox data: synthetic, no real user data
```

### Encryption in Transit
```
All API endpoints require TLS 1.3
Certificate: Let's Encrypt or enterprise CA
HSTS: enabled (max-age=31536000)
Cipher suites: only modern, AEAD-based
```

## Fraud Prevention Rules
```
Rule OF-1: Idempotency Abuse
  - Same idempotency key with different params in < 5 min
  - Action: Block for 15 min, notify developer

Rule OF-2: Rapid Key Creation
  - > 5 API keys created in 1 hour
  - Action: Rate limit key creation

Rule OF-3: Suspicious Payload
  - Payment description containing phishing patterns
  - Action: Flag for manual review

Rule OF-4: Webhook URL Tampering
  - URL changes to internal IP (10.x, 172.x, 192.168.x)
  - Action: Reject, notify developer

Rule OF-5: Sandbox Abuse
  - > 100 sandbox resets in 24h
  - Action: Rate limit resets, investigate
```

## Security Headers
```http
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'self'
X-XSS-Protection: 1; mode=block
Cache-Control: no-store
```
