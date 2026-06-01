# Logging Standard

> Single source of truth for logging conventions across ALL Beza Platform features. Every service, job, and script MUST conform to this standard.

## Log Format

### JSON Structure
```json
{
    "timestamp": "2025-05-29T10:30:00.123Z",
    "level": "info",
    "message": "Transfer completed successfully",
    "correlation_id": "c123e4567-e89b-12d3-a456-426614174000",
    "user_id": "u123e4567-e89b-12d3-a456-426614174000",
    "tenant_id": "t123e4567-e89b-12d3-a456-426614174000",
    "action": "transfer.create",
    "duration_ms": 234,
    "result": "success",
    "service": "wallet-service",
    "environment": "production",
    "version": "1.2.3",
    "request_id": "r123e4567-e89b-12d3-a456-426614174000",
    "data": {
        "transaction_id": "txn_uuid",
        "amount": 50000,
        "currency": "SYP",
        "sender_wallet_id": "w_uuid",
        "recipient_wallet_id": "w_uuid"
    },
    "error": null
}
```

### Required Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `timestamp` | ISO 8601 | Always | UTC, millisecond precision |
| `level` | string | Always | `debug` / `info` / `warn` / `error` / `fatal` |
| `message` | string | Always | Human-readable description in English |
| `correlation_id` | UUID v4 | Always | Tracing ID propagated from API gateway |
| `user_id` | UUID v4 | When available | Authenticated user ID. Null for unauthenticated |
| `tenant_id` | UUID v4 | When available | Tenant context |
| `action` | string | Always | Dot-notation: `{domain}.{entity}.{verb}` |
| `duration_ms` | integer | Operations | Time taken in milliseconds |
| `result` | string | Operations | `success` / `failure` / `timeout` |
| `service` | string | Always | Service name: e.g., `wallet-service` |
| `environment` | string | Always | `production` / `staging` / `development` |
| `version` | semver | Always | App version |
| `request_id` | UUID v4 | HTTP requests | Request ID from API gateway |

### Reserved `data` Fields (When Applicable)
| Field | Type | Description |
|-------|------|-------------|
| `transaction_id` | UUID | Transaction reference |
| `amount` | integer | Monetary amount (smallest unit) |
| `currency` | string | ISO 4217 currency code |
| `error_code` | string | Error catalog code (e.g., `WAL_001`) |
| `ip_address` | string | Client IP (masked) |
| `user_agent` | string | Client user agent |
| `http_method` | string | HTTP method |
| `http_path` | string | Request path |
| `http_status` | integer | Response status code |

## Log Levels

| Level | Color | When to Use | Example |
|-------|-------|-------------|---------|
| `debug` | Gray | Development only, verbose diagnostic info | SQL queries, HTTP request/response bodies |
| `info` | Blue | Normal operation events | "User created", "Transfer completed", "Settlement run started" |
| `warn` | Yellow | Unexpected but handled | "Rate limit almost reached (4/5)", "Retry attempt 2/3", "Deprecated API called" |
| `error` | Red | Operation failed, handled gracefully | "Transfer failed: insufficient balance", "SMS delivery failed, retrying" |
| `fatal` | Red (bold) | System unusable, immediate attention | "Database connection pool exhausted", "Out of memory" |

### Level Decision Tree
```
Is this a normal operation? → info
Will someone need this for debugging? → debug (disabled in prod unless sampled)
Does this indicate something unexpected but handled? → warn
Does this indicate a failure that was handled? → error
Does this indicate the system cannot continue? → fatal
```

## PII Masking

### Masking Rules
| Field | Mask Pattern | Example |
|-------|-------------|---------|
| Phone number | Show last 4 digits | `+963 944 XXX 456` |
| Email | Show first char + domain | `a***@domain.com` |
| Full name | Show first name only | `A***` |
| National ID | Show last 4 digits | `XXX1234` |
| PIN hash | Never log | `[REDACTED]` |
| Password | Never log | `[REDACTED]` |
| Token | Show first 8 chars | `eyJhbGci***` |
| IP address | Mask last octet | `192.168.1.XXX` |
| Bank account | Show last 4 digits | `XXXX1234` |
| Wallet balance | Log full (not PII) | `500000` |

### Implementation
```php
class SensitiveDataMasker
{
    private static array $patterns = [
        'phone' => ['pattern' => '/\b(\+?\d{1,3}[\s-]?)?(\d{1,4})[\s-]?(\d{1,4})[\s-]?(\d{4})\b/', 'replacement' => '$1$2XXX$4'],
        'email' => ['pattern' => '/(?<=.).(?=[^@]*?@)/', 'replacement' => '*'],
        'national_id' => ['pattern' => '/\b(\d{7})(\d{4})\b/', 'replacement' => 'XXXXXXX$2'],
    ];

    public static function mask(string $fieldName, string $value): string
    {
        if (in_array($fieldName, ['pin_hash', 'password', 'secret'])) {
            return '[REDACTED]';
        }

        return isset(self::$patterns[$fieldName])
            ? preg_replace(self::$patterns[$fieldName]['pattern'], self::$patterns[$fieldName]['replacement'], $value)
            : $value;
    }
}
```

## Context Enrichment

### Automatic Context (Middleware)
```php
class LogContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Correlation ID (from request or generate)
        $correlationId = $request->header('X-Correlation-ID')
            ?? $request->header('X-Request-ID')
            ?? (string) Str::uuid();

        Log::share('correlation_id', $correlationId);
        Log::share('request_id', $request->header('X-Request-ID') ?? (string) Str::uuid());
        Log::share('ip_address', $request->ip());
        Log::share('user_agent', $request->userAgent());

        // User context (after auth middleware)
        if ($user = $request->user()) {
            Log::share('user_id', $user->id);
            Log::share('tenant_id', $user->tenant_id);
        }

        return $next($request);
    }
}
```

## Log Storage & Retention

| Environment | Storage | Retention | Sampling |
|-------------|---------|-----------|----------|
| Production | AWS CloudWatch Logs | 90 days (standard), 1 year (errors/fatal) | debug: 1% sample |
| Staging | AWS CloudWatch Logs | 30 days | 100% |
| Development | Laravel log files | 7 days | 100% |

### Log Groups
```
/beza/production/wallet-service
/beza/production/auth-service
/beza/production/fx-service
/beza/production/remittance-service
/beza/production/notification-service
/beza/production/settlement-service
/beza/production/scheduler
/beza/production/api-gateway
```

## Logging Anti-Patterns

| Anti-Pattern | Why | Correct |
|-------------|-----|---------|
| Logging in loops | Blows up log volume, cost | Log summary after loop |
| Logging entire request body | May contain PII | Log specific fields only |
| `error_log()` or `var_dump()` | Breaks JSON format | Use `Log::info()` |
| Mismatched log levels | Misleading dashboards | Follow level decision tree |
| Missing correlation_id | Can't trace request | Always propagate from gateway |
| Logging passwords/tokens | Security incident | Always mask |
| Logging in catch without context | Useless log | Include error code, action, user_id |
