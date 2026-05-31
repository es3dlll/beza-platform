# Bill Payment Security

## Authentication & Authorization

### Bill Payment PIN
```php
// Bill payment uses wallet PIN (same as transfer)
// High-value bill payment (>200K SYP) requires additional step-up

public function authorizeBillPayment(User $user, int $amount, Device $device): bool
{
    // Base check: wallet PIN required (same as send money)

    // Step-up conditions:
    if ($amount > 200000) {
        return $this->stepUpAuth($user, 'biometric');
    }

    // First bill payment ever:
    if ($user->billTransactions()->count() === 0) {
        return $this->stepUpAuth($user, 'sms_otp');
    }

    // New device:
    if (!$device->isTrustedForUser($user)) {
        return $this->stepUpAuth($user, 'sms_otp');
    }

    return true;
}
```

### Biller API Authentication
```
Each biller integration uses biller-specific authentication:

PEED: API Key + HMAC-SHA256 request signing
  - Key rotated every 30 days
  - HMAC includes: endpoint + timestamp + request body + nonce
  - Timestamp must be within 60 seconds

Syriatel: Client Certificate + Username/Password
  - Mutual TLS (mTLS) with client cert
  - Cert stored in Vault, rotated every 90 days
  - Credentials encrypted at rest

MTN: OAuth2 Client Credentials
  - Access token: TTL 3600s (cached in Redis)
  - Refresh: automatic before expiry
  - Scope: bill_payment

Government CSV: SFTP with SSH Key
  - Separate key pair for each ministry
  - Key password-protected
  - Source IP restricted to Beza production IPs
```

## Sensitive Actions
```
Actions requiring step-up authentication:
  - Pay bill > 200,000 SYP
  - Change auto-pay settings
  - Cancel a scheduled bill
  - Request bill payment refund
  - Add new biller customer ID to schedule
```

## Fraud Prevention Rules

### Bill Payment-Specific Rules
```
Rule BP-1: Customer ID Velocity
  - No more than 10 bill fetches for different customer IDs in 5 minutes
  - Action: Temp block fetch for 15 minutes

Rule BP-2: Bill Amount Anomaly
  - Bill amount > 3x the average of last 3 payments for same customer ID
  - Action: Flag for manual review before payment

Rule BP-3: New Customer ID Payment
  - First payment to a never-before-used customer ID limited to 50,000 SYP
  - Action: Lower limit until 3 successful payments to same ID

Rule BP-4: Rapid Bill Pay
  - More than 5 bill payments in 10 minutes
  - Action: Step-up auth required for next payment

Rule BP-5: Biller API Abuse
  - Bill fetch without subsequent pay (ratio > 10:1 in 1 hour)
  - Action: Temp block fetch for the customer ID

Rule BP-6: Stale Bill Payment
  - Paying a bill fetched > 30 minutes ago (amount may have changed)
  - Action: Force re-fetch before payment

Rule BP-7: Multiple Bills Same ID Same Day
  - Paying same customer ID more than once in same day
  - Action: Verify with biller if duplicate payment

Rule BP-8: CSV Bill Scraping
  - Multiple customer ID queries against CSV batch data in short period
  - Action: Rate limit CSV queries to 5/minute
```

### Implementation
```php
class BillFraudGuard
{
    public function __construct(
        private Cache $cache,
        private EventService $eventService,
    ) {}

    public function checkFetchFraud(User $user, string $customerId): FraudCheckResult
    {
        $cacheKey = "fraud:bill-fetch:{$user->id}";

        // Rule BP-1: Customer ID velocity
        $recentIds = $this->cache->get($cacheKey, []);
        $recentIds[] = ['id' => $customerId, 'time' => now()];
        $recentIds = array_filter($recentIds, fn($r) => $r['time']->diffInMinutes(now()) < 5);
        $this->cache->put($cacheKey, $recentIds, 300);

        $uniqueIds = collect($recentIds)->pluck('id')->unique();
        if ($uniqueIds->count() > 10) {
            $this->eventService->emitFraudAlert($user, 'bp_1', 'Customer ID velocity exceeded');
            return FraudCheckResult::blocked('Too many fetches', 15);
        }

        return FraudCheckResult::allowed();
    }

    public function checkPaymentFraud(
        User $user, Bill $bill, int $amount, string $customerId
    ): FraudCheckResult {
        // Rule BP-3: New customer ID
        $history = BillTransaction::where('user_id', $user->id)
            ->where('customer_id', $customerId)
            ->where('status', 'paid')
            ->count();

        if ($history === 0 && $amount > 50000) {
            return FraudCheckResult::blocked('New customer ID exceeds 50K limit', 0);
        }

        // Rule BP-2: Amount anomaly
        if ($history >= 3) {
            $avgAmount = BillTransaction::where('user_id', $user->id)
                ->where('customer_id', $customerId)
                ->where('status', 'paid')
                ->latest('paid_at')
                ->take(3)
                ->avg('bill_amount');

            if ($amount > $avgAmount * 3) {
                $this->eventService->emitFraudAlert($user, 'bp_2', 'Amount anomaly detected');
                return FraudCheckResult::flagged('Amount 3x above average');
            }
        }

        return FraudCheckResult::allowed();
    }
}
```

## Biller API Secrets Management
```yaml
# Secrets stored in HashiCorp Vault
secrets:
  peed:
    api_key: "peed_api_key_value"
    hmac_secret: "hmac_shared_secret"
  syriatel:
    client_cert: "-----BEGIN CERTIFICATE-----..."
    client_key: "-----BEGIN RSA PRIVATE KEY-----..."
    username: "beza_integration"
    password: "syriatel_b2b_password"
  mtn:
    client_id: "mtn_beza_client_id"
    client_secret: "mtn_beza_client_secret"
  csv_gateway:
    ssh_private_key: "ssh_rsa_private_key"
    ssh_passphrase: "key_passphrase"
```

## Audit Logging
```php
// Every biller API interaction is logged to biller_connection_logs
// Includes full request/response for audit trail
// Logs immutable (append-only) for compliance

$log = BillerConnectionLog::create([
    'biller_id' => $biller->id,
    'biller_type' => $biller->type,
    'operation' => $operation,     // fetch, pay, status_check
    'customer_id' => $customerId,
    'request_url' => $url,
    'request_body' => json_encode($requestPayload),
    'response_body' => json_encode($responsePayload),
    'http_status' => $httpStatus,
    'success' => $success,
    'error_message' => $errorMessage,
    'duration_ms' => $duration,
]);
```
