# FX Engine Security

## Rate Manipulation Protection

### Rate Validation Rules
```php
class RateValidator
{
    // Maximum allowed deviation from last known rate
    private const MAX_DEVIATION_PCT = 20; // 20%

    // Maximum allowed spread (Beza rate vs mid-market)
    private const MAX_SPREAD_PCT = 5; // 5%

    // Minimum rate value (prevents zero/negative manipulation)
    private const MIN_RATE_VALUE = [
        'SYP/USD' => 1000,    // 1,000 SYP/USD absolute floor
        'SYP/EUR' => 1000,    // 1,000 SYP/EUR absolute floor
        'USD/EUR' => 0.1,     // 0.1 USD/EUR absolute floor
    ];

    // Rate must pass all validation before being accepted
    public function validate(RateResult $rate, CurrencyPair $pair, RateResult $lastRate): ValidationResult
    {
        $checks = [];

        // 1. Minimum value check
        if ($rate->mid < self::MIN_RATE_VALUE[$pair->value]) {
            $checks[] = new ValidationFailure('RATE_BELOW_MINIMUM',
                "Rate {$rate->mid} below minimum " . self::MIN_RATE_VALUE[$pair->value]
            );
        }

        // 2. Deviation check
        if ($lastRate) {
            $deviation = abs($rate->mid - $lastRate->mid) / $lastRate->mid * 100;
            if ($deviation > self::MAX_DEVIATION_PCT) {
                $checks[] = new ValidationFailure('RATE_DEVIATION_EXCEEDED',
                    "Rate deviation {$deviation}% exceeds " . self::MAX_DEVIATION_PCT . "%"
                );
            }
        }

        // 3. Spread integrity
        $spread = abs($rate->ask - $rate->bid) / $rate->mid * 100;
        if ($spread > self::MAX_SPREAD_PCT) {
            $checks[] = new ValidationFailure('SPREAD_EXCEEDS_LIMIT',
                "Spread {$spread}% exceeds " . self::MAX_SPREAD_PCT . "%"
            );
        }

        // 4. Bid/Ask order integrity
        if ($rate->bid >= $rate->ask) {
            $checks[] = new ValidationFailure('INVALID_BID_ASK',
                "Bid {$rate->bid} must be less than ask {$rate->ask}"
            );
        }

        // 5. Rate freshness
        if ($rate->timestamp->diffInSeconds(now()) > 60) {
            $checks[] = new ValidationFailure('STALE_RATE',
                "Rate is " . $rate->timestamp->diffInSeconds(now()) . "s old (max 60s)"
            );
        }

        return new ValidationResult($checks);
    }
}
```

## Maximum Spread Limits

### Hard Limits (enforced at code level)
```php
// config/fx.php
return [
    'spreads' => [
        'SYP/USD' => [
            'max' => 0.05,     // 5% hard cap (regulatory + security)
            'basic' => 0.04,   // 4% for basic tier
            'standard' => 0.03, // 3% for standard tier
            'premium' => 0.015, // 1.5% for premium tier
            'merchant' => 0.02, // 2% for merchant tier
        ],
        'SYP/EUR' => [
            'max' => 0.05,     // 5% hard cap
            'basic' => 0.045,
            'standard' => 0.035,
            'premium' => 0.02,
            'merchant' => 0.025,
        ],
        'USD/EUR' => [
            'max' => 0.03,     // 3% hard cap (tighter for major pairs)
            'basic' => 0.02,
            'standard' => 0.015,
            'premium' => 0.0075,
            'merchant' => 0.01,
        ],
    ],
    'max_spread' => 0.05, // Global hard cap — cannot be exceeded by admin override

    // Admin override limits
    'override' => [
        'max_rate' => 50000,    // Cannot set rate above 50,000 SYP/USD
        'min_rate' => 1000,     // Cannot set rate below 1,000 SYP/USD
        'cooldown_seconds' => 300, // 5 minutes between overrides
        'requires_2fa' => true,
        'max_duration_minutes' => 60, // Override auto-expires after 60 min
    ],
];
```

## Rate Lock Expiry Enforcement

### Redis + DB Dual Enforcement
```php
class RateLockEnforcement
{
    // Lock TTL: 30 seconds (configurable per user tier)
    // Enforced at 3 levels:

    // Level 1: Redis TTL (primary)
    // Lua script SET with EXPIRE 30 — automatic expiry
    // Key: fx:lock:SYP/USD:user_42 automatically deleted after 30s

    // Level 2: Application layer (on conversion attempt)
    public function validateLock(FxRateLock $lock): void
    {
        if ($lock->expires_at->isPast()) {
            $lock->status = RateLockStatus::EXPIRED;
            $lock->save();
            event(new RateExpired($lock));
            throw new RateLockExpiredException("Lock {$lock->lockId} expired");
        }
    }

    // Level 3: Cron job (every 10 seconds, cleanup stale locks)
    public function expireStaleLocks(): int
    {
        $expired = FxRateLock::where('status', RateLockStatus::ACTIVE)
            ->where('expires_at', '<', now())
            ->update(['status' => RateLockStatus::EXPIRED]);

        if ($expired > 0) {
            logger()->info("Expired {$expired} stale rate locks");
        }

        return $expired;
    }
}
```

## Admin Rate Override Audit Trail

### Audit Requirements
```
Every rate override MUST capture:
  - Who: admin user ID, name, role
  - What: pair, old rate, new rate
  - Why: reason (free text, min 10 chars)
  - When: timestamp (server time, accurate to ms)
  - Where: IP address, user agent, session ID
  - Approval: 2FA TOTP token
  - Duration: override start + auto-expiry time
  - Effect: which providers were affected, how Beza rate changed

Audit storage:
  Table: fx_rate_overrides (separate audit trail, append-only)
  Retention: 10 years (financial + regulatory)
  Immutable: INSERT-only, no UPDATE/DELETE allowed

SQL:
CREATE TABLE fx_rate_overrides (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pair            ENUM('SYP/USD', 'SYP/EUR', 'USD/EUR') NOT NULL,
    old_rate        DECIMAL(14, 4) NOT NULL,
    new_rate        DECIMAL(14, 4) NOT NULL,
    reason          VARCHAR(500) NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL,
    effective_from  TIMESTAMP NOT NULL,
    effective_until TIMESTAMP NOT NULL,
    overridden_by   BIGINT UNSIGNED NOT NULL,
    overridden_by_name VARCHAR(100) NOT NULL,
    overridden_by_role VARCHAR(50) NOT NULL,
    twofa_token     VARCHAR(64) NOT NULL,
    ip_address      VARCHAR(45) NOT NULL,
    user_agent      VARCHAR(500) NULL,
    session_id      VARCHAR(64) NULL,
    affected_providers INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_overrides_pair (pair),
    INDEX idx_overrides_by (overridden_by),
    INDEX idx_overrides_created (created_at),
    FOREIGN KEY (overridden_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Provider Credential Encryption

### Encryption Strategy
```php
class ProviderCredentialEncryption
{
    // Provider API keys, secrets, and tokens are encrypted at rest
    // Uses Laravel's built-in encryption (AES-256-CBC)
    // Encryption key stored in environment: FX_PROVIDER_ENCRYPTION_KEY

    public function encrypt(string $plaintext): string
    {
        // Using a dedicated key separate from APP_KEY
        $key = base64_decode(config('fx.provider_encryption_key'));
        $cipher = 'aes-256-cbc';
        $iv = random_bytes(openssl_cipher_iv_length($cipher));

        $encrypted = openssl_encrypt($plaintext, $cipher, $key, 0, $iv);

        return base64_encode($iv . '::' . $encrypted);
    }

    public function decrypt(string $ciphertext): string
    {
        $key = base64_decode(config('fx.provider_encryption_key'));
        $cipher = 'aes-256-cbc';

        $data = base64_decode($ciphertext);
        $parts = explode('::', $data, 2);
        $iv = $parts[0];
        $encrypted = $parts[1];

        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }

    // Credentials are NEVER logged, displayed in UI, or exposed in API responses
    // Admin can see: provider name, status, health, type
    // Admin CANNOT see: API keys, tokens, secrets
    // Credentials are only decrypted in memory during rate fetch execution
}
```

## Additional Security Controls

```
1. Rate Fetch Authentication:
   - Internal rate fetch endpoints require service-to-service JWT
   - Provider webhooks authenticated via HMAC signature
   - Scraper fleet on isolated network segment

2. Conversion Authorization:
   - PIN required for every conversion
   - PIN pepper (config secret) + bcrypt cost 12
   - Device binding: conversion must come from trusted device
   - Large conversions (>$5K): biometric step-up required

3. Rate Lock Anti-Scraping:
   - Max 1 active rate lock per user
   - Max 10 rate locks per user per hour
   - Rate lock amount must be within user's actual wallet balance
   - IP-based rate limiting: 100 lock attempts/min per IP

4. Audit Logging (All FX Operations):
   - Rate fetch requests (who requested, what rates)
   - Rate lock attempts (success, failure, reason)
   - Conversions (full conversion + rate details)
   - Provider status changes (with before/after state)
   - Overrides (full details as above)
   - Anomaly detections (with all detection data)

5. Secrets Management:
   - Provider credentials stored in Vault (HashiCorp)
   - Kubernetes secrets for DB, Redis passwords
   - FX encryption key rotated every 90 days
   - No secrets in code, config files, or environment dumps
```
