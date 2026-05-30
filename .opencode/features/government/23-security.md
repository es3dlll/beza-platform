# Government Collections Security

## Authentication & Authorization

### Payment Access Control
```php
// Policy: Only the wallet owner (or guest with token) can access
class GovernmentTransactionPolicy
{
    public function view(User $user, GovernmentTransaction $txn): bool
    {
        return $txn->user_id === $user->id;
    }

    public function pay(User $user, GovernmentBiller $biller): bool
    {
        // All verified users can pay government fees
        // Guest payments limited to 500,000 SYP per transaction
        return $user->is_verified || $user->is_guest;
    }

    public function receipt(User $user, GovernmentReceipt $receipt): bool
    {
        // Receipt accessible to payer and ministry auditors
        return $receipt->transaction->user_id === $user->id
            || $user->hasRole('ministry_auditor');
    }

    public function reconcile(User $user): bool
    {
        // Only finance team and system admins
        return $user->hasRole('finance_admin')
            || $user->hasRole('super_admin');
    }
}
```

### Sensitive Actions (Require PIN or Biometric)
```php
'government:payment' => [
    'pin_required' => true,
    'biometric_allowed' => true,
    'max_amount_no_pin' => 50000,  // Under 50K SYP, PIN not required
    'daily_limit_without_pin' => 200000,
    'session_timeout_minutes' => 5, // Re-authenticate after 5 min idle
],

'government:save_payer' => [
    'pin_required' => false,        // Saving a reference doesn't need PIN
],

'government:receipt_share' => [
    'pin_required' => false,
],
```

## Data Encryption

### At Rest
```php
// Sensitive fields in government_transactions
Schema::table('government_transactions', function ($table) {
    // Tax IDs, passport numbers, student IDs encrypted
    $table->text('biller_reference_encrypted')  // AES-256-CBC
          ->nullable()
          ->after('biller_reference');

    $table->text('ministry_reference_encrypted')
          ->nullable()
          ->after('ministry_reference');
});

// Receipt QR data — signed but not encrypted (public verification)
// Receipt PDF — encrypted at storage level (S3 server-side encryption)

// Saved payers — fully encrypted
Schema::table('saved_payers', function ($table) {
    $table->text('reference_id_encrypted');
});
```

### In Transit
- All API calls to ministries over TLS 1.3 with mutual TLS (client certificate)
- Ministry file uploads via SFTP with SSH key authentication
- Beza internal APIs always HTTPS with API tokens
- Database connections encrypted (TLS)

## Ministry API Security

### Adapter Authentication
```php
interface MinistryAuthStrategy {
    public function authenticate(): AuthToken;
    public function signRequest(array $payload): string;   // HMAC signature
    public function verifyResponse(string $signature, array $payload): bool;
}

// MoF: JWT with client certificate
class MofAuthStrategy implements MinistryAuthStrategy { ... }

// MoI: API key + HMAC-SHA256
class MoiAuthStrategy implements MinistryAuthStrategy { ... }

// TRAF: SFTP key-based + file signing with GPG
class TrafAuthStrategy implements MinistryAuthStrategy { ... }
```

## Idempotency & Duplicate Prevention

```php
// Idempotency key stored in government_transactions.idempotency_key (unique index)
// On retry with same key → return previous result (HTTP 200 with existing data)
// On retry after timeout → return 409 Conflict
// Keys expire after 24 hours via cleanup job

class IdempotencyMiddleware
{
    public function handle($request, $next)
    {
        $key = $request->header('Idempotency-Key');
        if (!$key) {
            return response()->json([
                'status' => 'error',
                'error' => ['code' => 'IDEMPOTENCY_KEY_REQUIRED']
            ], 400);
        }

        $existing = GovernmentTransaction::where('idempotency_key', $key)->first();
        if ($existing) {
            return response()->json([
                'status' => 'success',
                'data' => $this->buildExistingResponse($existing),
                'duplicate' => true,
            ], 200);
        }

        return $next($request);
    }
}
```

## Audit Logging

```php
// Every government transaction logs:
class GovernmentAuditLog
{
    // Required fields
    'transaction_id',           // gov_txn_abc123
    'user_id',                  // 42 (null for guest)
    'action',                   // query, pay, refund, settle, reconcile
    'service_type',             // tax_income, passport, tuition
    'biller_reference',         // Masked: 253*******51
    'amount',                   // 263812
    'status',                   // completed, failed
    'ip_address',              // 185.xx.xx.xx
    'user_agent',              // BezaApp/2.0 (Android 14)
    'request_payload_hash',    // SHA-256 of request
    'response_payload_hash',   // SHA-256 of response
    'ministry_request_id',     // Ministry correlation ID
    'duration_ms',             // 1234
    'created_at',
}

// Audit logs are append-only, stored in separate table
// Retention: 7 years (government financial regulation)
// Archival to cold storage after 1 year
```

## Security Incident Scenarios

| Scenario | Detection | Response |
|----------|-----------|----------|
| Brute force tax ID lookup | Rate limit exceeded | Temp block IP → CAPTCHA → permanent block |
| Payment with stolen wallet | Device fingerprint mismatch + unusual location | Flag for review → hold settlement → notify user |
| Ministry API key compromised | Anomalous API call pattern | Rotate key → revoke compromised → audit all transactions |
| Receipt forgery attempt | QR verification failed | Log attempt → block IP → notify ministry |
| Replay attack on payment | Idempotency key reused | Return existing result, log duplicate attempt |
| Man-in-the-middle on ministry call | TLS certificate mismatch | Abort connection → alert on-call → investigate |
