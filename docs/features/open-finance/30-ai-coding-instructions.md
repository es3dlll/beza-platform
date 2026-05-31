# Open Finance AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the Open Finance feature. Follow these specifications precisely.

## Implementation Order
```
Phase 1 (Files 1-10): Database migrations + Models + Enums
Phase 2 (Files 11-20): Repositories + Services + Actions
Phase 3 (Files 21-30): Controllers + API routes + Policies
Phase 4 (Files 31-40): Events + Listeners + Jobs
Phase 5 (Files 41-50): Tests + Factories
Phase 6 (Files 51-60): Flutter screens + Providers + Widgets
```

## Migration Files to Create

### 1. Create Developer Accounts Table
```php
// database/migrations/2026_01_01_010001_create_developer_accounts_table.php
// Schema definition in 16-database-schema.md
// Fields: id, user_id, tenant_id, email (unique), company_name, company_website,
//         phone, password_hash, tier (enum:free,startup,business,enterprise),
//         kyc_status (enum:pending,approved,rejected), kyc_approved_at, is_active,
//         metadata (json), timestamps
// Indexes: user_id, tier, email
// Foreign keys: user_id → users.id, tenant_id → tenants.id
```

### 2. Create API Keys Table
```php
// Fields: id, developer_id, label, key_prefix, key_hash (unique, sha256),
//         environment (enum:sandbox,production), scopes (json),
//         status (enum:active,revoked,expired), last_used_at, expires_at,
//         revoked_at, timestamps
// Indexes: developer_id, status, environment
```

### 3. Create OAuth Clients Table
```php
// Fields: id, developer_id, client_id (unique), client_secret (sha256),
//         name, grant_types (json), redirect_uris (json), default_scopes (json),
//         is_confidential, timestamps
```

### 4. Create OAuth Tokens Table
```php
// Fields: id, oauth_client_id, token_hash (unique, sha256), scopes (json),
//         expires_at, revoked_at, created_at
```

### 5. Create Webhook Endpoints Table
```php
// Fields: id, developer_id, url, signing_secret, events (json),
//         description, status (enum:active,paused,disabled), timestamps
```

### 6. Create Webhook Deliveries Table
```php
// Fields: id, webhook_endpoint_id, event_type, payload (json),
//         status (enum:pending,delivered,failed), attempts, max_attempts,
//         response_code, response_body, last_error, delivered_at,
//         next_retry_at, timestamps
```

### 7. Create API Usage Logs Table
```php
// Fields: id, developer_id, tenant_id, api_key_id, method, endpoint,
//         status_code, latency_ms, ip_address, user_agent, request_id,
//         idempotency_key, error_code, request_body (json), response_body (json),
//         created_at
// Partitioned by month
```

### 8. Create Rate Limit Config Table
```php
// Seeded with initial limits for free, startup, business, enterprise tiers
```

### 9. Create Sandbox Accounts Table
```php
// Fields: id, developer_id, phone, balance_syp, balance_usd, is_default, timestamps
```

## Model Files to Create

### ApiKey Model
```php
// app/Modules/OpenFinance/Models/ApiKey.php
// Relations: developer()
// Scopes: active(), byEnvironment(), byScope()
// Methods: isExpired(), isRevoked(), hasScope($scope), touchLastUsed()
// Casts: scopes (array), environment (ApiKeyEnvironment enum), status (string)
```

### DeveloperAccount Model
```php
// Relations: apiKeys(), oauthClients(), webhookEndpoints(), usageLogs()
// Methods: isKycApproved(), canCreateProductionKeys(), getTierLimits()
// Casts: tier (DeveloperTier enum), kyc_status (string), metadata (array)
```

### WebhookEndpoint Model
```php
// Relations: developer(), deliveries()
// Scopes: active(), subscribedToEvent()
// Methods: isActive(), subscribesTo(), generateSecret()
```

## Service Implementation Notes

### ApiKeyService
```php
// createKey(): generate random key → hash → store hash → return raw key once
// validateKey(): hash input → lookup → check expiry/revocation → touch last used
// revokeKey(): set revoked_at, status = revoked
// rotateKey(): expire old in 24h, create new with same params
```

### OAuthService
```php
// issueClientCredentialsToken(): validate client_id + secret → generate token → hash → store → return
// validateToken(): hash input → lookup valid token → return developer
// refreshToken(): validate refresh token → revoke old → issue new
```

### RateLimitService
```php
// Uses Redis increment with TTL for sliding window
// Per-minute and per-day counters
// Tier limits from rate_limit_config table
// Returns Retry-After header on limit exceeded
```

### WebhookDeliveryService
```php
// deliver(): find subscribed endpoints → queue delivery jobs per endpoint
// sendWithRetry(): POST to URL with HMAC signature → log delivery →
//   retry 3x with exponential backoff (1min, 2min, 4min)
// signPayload(): HMAC-SHA256 of JSON payload with signing secret
```

### SandboxService
```php
// In-memory simulated ledger (no CFE dependency)
// Pre-seeded test accounts with 1M SYP, $10K USD
// Supports all API endpoints with realistic responses
// reset(): clear all simulated data, restore initial state
```

## Flutter Implementation Notes

### State Management
- Use Riverpod with code generation
- Providers: dashboardProvider, apiKeyListProvider, webhookConfigProvider
- API client interceptor: auto-attach API key, handle 401/429

### Screens
1. DashboardScreen: Stats cards, usage chart, recent requests
2. ApiKeyListScreen: List, create, rotate, revoke
3. WebhookConfigScreen: URL, events, delivery log
4. SandboxScreen: Reset, test accounts, webhook inspector
5. ApiReferenceScreen: Browseable API docs
6. PlaygroundScreen: Interactive API tester

## Testing Requirements
- Minimum 80% code coverage on services
- All API endpoints have 200, 400, 401, 403, 422 response tests
- E2E tests for: developer registration, payment initiation, webhook delivery
- Flutter widget tests for all screens (loading, empty, error states)
- Webhook retry logic tested with HTTP fakes
