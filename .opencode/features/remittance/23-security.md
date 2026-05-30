# Remittance Security

## Authentication & Authorization

### PIN Security
```php
// PIN creation during remittance setup (same as wallet)
public function createPin(User $user, string $plainPin): void
{
    // Requirements: 6 digits, cannot be sequential or repeated (123456, 111111)
    $this->validatePinStrength($plainPin);

    // Hash: bcrypt cost=12 with pepper
    $pepper = config('beza.security.pin_pepper');
    $hashed = Hash::make($plainPin . $pepper, ['rounds' => 12]);

    UserPin::updateOrCreate(
        ['user_id' => $user->id],
        ['pin_hash' => hash('sha256', $hashed)]
    );
}
```

### Step-Up Authentication Thresholds
```
Remittance-specific actions requiring step-up auth:

  Local P2P (SYP):
    > 500,000 SYP send: PIN + biometric
    > 2,000,000 SYP send: PIN + biometric + SMS OTP
    First transfer to new recipient: biometric required

  Diaspora Remittance (USD/EUR):
    > $500 equivalent: PIN + biometric + source of funds prompt
    > $1,000 equivalent: PIN + biometric + SMS OTP + source of funds
    First transfer to new beneficiary: biometric + SMS OTP
    New device + first remittance: video verification

  Recurring Transfers:
    Setup: PIN + biometric
    Modify amount: PIN + biometric + SMS OTP
    Cancel: PIN only

  Beneficiary Management:
    Add beneficiary: biometric
    Edit beneficiary phone: PIN + SMS OTP
    Delete beneficiary: PIN + biometric

  Sensitive Actions:
    Change remittance PIN: biometric + SMS OTP
    Increase daily limit: biometric + video call with compliance
    Unlock account: biometric + SMS OTP + compliance review
```

### Device Binding
```php
public function authorizeRemittance(User $user, Device $device, string $ip,
    float $amount, string $currency, string $corridor): AuthResult
{
    // Check 1: Device is known for this user
    if (!$device->isTrustedForUser($user)) {
        return AuthResult::stepUp('sms_otp + biometric');
    }

    // Check 2: IP reputation (VPN, Tor, blacklisted)
    if ($this->ipReputation->isSuspicious($ip)) {
        return AuthResult::stepUp('sms_otp');
    }

    // Check 3: Diaspora-specific — IP country must match registered country
    $userCountry = $user->registered_country;
    $ipCountry = $this->geoIp->lookup($ip);
    if ($userCountry !== $ipCountry && $amount > 100) {
        // Sender IP doesn't match registered country — high risk
        $this->eventService->emitComplianceAlert($user, 'IP_COUNTRY_MISMATCH');
        return AuthResult::stepUp('video_verification');
    }

    // Check 4: Amount-based step-up
    if ($amount > 500) {
        return AuthResult::stepUp('biometric');
    }

    // Check 5: Behavioral risk score
    $risk = $this->behaviorAnalyzer->score($user, $device, $ip);
    if ($risk > 70) {
        return AuthResult::blocked('High risk score');
    }
    if ($risk > 40) {
        return AuthResult::stepUp('biometric');
    }

    return AuthResult::allowed();
}
```

## Source of Funds Checks (Diaspora Senders)

### Trigger Thresholds
```
All diaspora remittances require source of funds declaration:

  < $500:     Self-declaration (dropdown: salary, savings, business, gift, other)
  $500-$1K:  Self-declaration + last 3 months bank statement
  $1K-$5K:   Self-declaration + bank statement + income proof (pay slip/tax return)
  > $5K:     Enhanced due diligence + video interview + full financial disclosure
```

### Implementation
```php
public function validateSourceOfFunds(User $user, float $amount, string $source): void
{
    if ($amount < 500) return; // No proof needed

    if ($amount >= 500 && $amount < 1000) {
        // Self-declaration only
        $user->update(['source_of_funds' => $source]);
        return;
    }

    if ($amount >= 1000 && $amount < 5000) {
        // Require document upload
        $user->requireKycLevel(3); // Enhanced KYC
        $this->requireDocumentUpload($user, 'bank_statement');
        $this->requireDocumentUpload($user, 'income_proof');
        return;
    }

    // >= $5,000: Full enhanced due diligence
    $this->complianceService->initiateEDD($user, $amount);
}
```

## Country Sanctions Screening

### Per-Corridor Screening Rules
```
Corridor: EUR_DE->SYP (Germany → Syria)
  Sender checks:
    - UN Sanctions List (Syria-specific)
    - EU Sanctions List (Syria + Russia)
    - German BAFin sanctions list
    - PEP (Politically Exposed Person) check
  Recipient checks:
    - UN Sanctions List (Syria-specific)
    - OFAC SDN List (Syria)
    - EU Sanctions List (Syria)
    - CBL (Central Bank of Lebanon) sanctions

Corridor: USD_US->SYP (USA → Syria)
  Sender checks:
    - OFAC SDN List (primary)
    - UN Sanctions List
    - FinCEN 314(a) list
    - PEP check
  Recipient checks:
    - OFAC SDN List (Syria-specific, extensive)
    - UN Sanctions List
    - EU Sanctions List
    - CBL sanctions

Screening Frequency:
  - Every transfer: sender + recipient screened in real-time
  - Beneficiary: screened on creation and re-screened every 30 days
  - Recurring: recipient re-screened before each execution
```

## Daily/Monthly Limits by Corridor

| Corridor | Daily Max (Sender) | Monthly Max (Sender) | Per Txn Max | Per Txn Min | KYC Required |
|----------|-------------------|---------------------|-------------|-------------|--------------|
| SYP→SYP (local) | 2,000,000 SYP | 20,000,000 SYP | 1,000,000 SYP | 1,000 SYP | 0 |
| USD→USD (local) | $2,000 | $20,000 | $1,000 | $1 | 1 |
| EUR→SYP (Germany) | €2,000 | €20,000 | €1,000 | €10 | 2 |
| EUR→SYP (Sweden) | €1,500 | €15,000 | €800 | €10 | 2 |
| USD→SYP (USA) | $2,000 | $25,000 | $1,000 | $10 | 2 |
| USD→SYP (UAE) | $3,000 | $30,000 | $2,000 | $10 | 2 |
| USD→SYP (Saudi) | $3,000 | $30,000 | $2,000 | $10 | 2 |
| TRY→SYP (Turkey) | 5,000 TRY | 50,000 TRY | 3,000 TRY | 50 TRY | 1 |
| EUR→USD (Germany) | €5,000 | €50,000 | €3,000 | €10 | 2 |
| USD→USD (USA same) | $5,000 | $50,000 | $3,000 | $10 | 2 |

### Dynamic Limit Adjustments
```
AI-based limit adjustments (see 28-ai-integration.md):
  - Low-risk user (6+ months history, clean): 1.5x standard limit
  - High-risk user (new account, flagged pattern): 0.5x standard limit
  - VIP/repeat sender (>$10K total sent): 2x standard limit
  - Recurring users: individual execution counts toward monthly, not daily
```

## Fraud Prevention Rules
```
Rule R-1: Velocity Check
  - No more than 3 transfers to different beneficiaries within 10 minutes
  - No more than 5 transfers total within 30 minutes
  - Action: Block + step-up auth

Rule R-2: New Beneficiary Amount Limit
  - First transfer to new beneficiary: max $200 (or equivalent)
  - Increases with successful transfers and time

Rule R-3: Round Amount Detection
  - Transfers of exact round amounts ($500, $1,000) checked against known patterns
  - Structuring detection: multiple round amounts below reporting threshold

Rule R-4: Geographic Anomaly
  - Sender IP from different country than registered residence
  - Action: Flag + step-up auth for >$100

Rule R-5: Night Activity
  - 23:00-06:00 local time diaspora remittances >$300 flagged for review

Rule R-6: Rapid Registration
  - User registered < 24h ago: max send $50
  - User registered < 7 days ago: max send $200

Rule R-7: Recipient Velocity
  - Same recipient receiving >3 remittances in 1 hour flagged
  - >5 in 1 hour → auto-block

Rule R-8: FX Rate Arbitrage Detection
  - Multiple rate locks without execution (lock abandonment)
  - >3 abandoned locks in 1 hour → rate lock disabled for 24h

Rule R-9: Recurring Deviation
  - Recurring amount changes by >50% from historical average flagged
  - Recurring to new beneficiary (was to different person) flagged
```
