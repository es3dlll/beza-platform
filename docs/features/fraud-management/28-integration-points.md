# Integration Points — Fraud Management

## Cross-Cutting Feature

Fraud Management is a **CROSS-CUTTING** feature that integrates with ALL other Beza platform features. This document defines the integration contracts and data flow.

## Integration Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       BEZA MODULAR MONOLITH                                 │
│                                                                             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐        │
│  │ Wallet   │ │ Agent    │ │Remittance│ │ Merchant │ │ Bills    │        │
│  │ Module   │ │ Module   │ │ Module   │ │ Module   │ │ Module   │        │
│  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘        │
│       │             │            │            │            │              │
│       └─────────────┴────────────┴────────────┴────────────┘              │
│                                    │                                      │
│                                    ▼                                      │
│                     ┌──────────────────────────┐                          │
│                     │      FraudEngine          │                         │
│                     │      Module                │                        │
│                     └────┬────────────┬─────────┘                         │
│                          │            │                                   │
│                          ▼            ▼                                   │
│               ┌──────────────┐ ┌──────────────┐                          │
│               │  Compliance  │ │  Notification │                         │
│               │  Module      │ │  Module       │                         │
│               └──────┬───────┘ └──────┬───────┘                          │
│                      │                │                                   │
│                      ▼                ▼                                   │
│               ┌──────────────┐ ┌──────────────┐                          │
│               │  CBS / AML   │ │  SMS / Push  │                          │
│               │  Portal      │ │  Gateway     │                          │
│               └──────────────┘ └──────────────┘                          │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Integration Matrix

| Feature Module             | Integration Type                 | Data Shared                                         | Fraud Type Detected                     | Synchronous?        |
| -------------------------- | -------------------------------- | --------------------------------------------------- | --------------------------------------- | ------------------- |
| **Wallet**                 | Event: TransactionInitiated      | Sender, recipient, amount, device, location         | ATO, Mule, Social Engineering, Phishing | Yes (200ms SLA)     |
| **Agent**                  | Event: AgentTransactionInitiated | Agent ID, customer ID, amount, float, location      | Agent Fraud, Float Theft, Ghost Agent   | Yes (200ms)         |
| **Remittance**             | Event: RemittanceInitiated       | Sender (diaspora), recipient, amount, corridor, SIM | SIM Swap, Remittance Intercept          | Yes (200ms)         |
| **Merchant**               | Event: MerchantPaymentInitiated  | Merchant ID, customer ID, amount, device            | Merchant Collusion, Promo Abuse         | Yes (200ms)         |
| **Bills**                  | Event: BillPaymentInitiated      | Payer, biller, amount, bill type                    | Unusual Bill Payment, Stolen Account    | Yes (200ms)         |
| **Payroll**                | Event: BulkDisbursementInitiated | Employer, employee list, amounts, batch             | Salary Diversion, Ghost Employee        | Yes (300ms — batch) |
| **Onboarding/KYC**         | Event: UserRegistered            | User ID, KYC level, device, location                | Synthetic Identity, Mule Creation       | Async               |
| **Login/Auth**             | Event: UserLoggedIn              | User ID, device, IP, session                        | ATO, Device Anomaly                     | Yes (100ms)         |
| **Password Reset**         | Event: PasswordReset             | User ID, device, IP                                 | Account Takeover attempt                | Yes (100ms)         |
| **Device Management**      | Event: NewDeviceDetected         | User ID, device FP, device name                     | Device Anomaly                          | Async               |
| **SIM Change**             | Event: SIMChanged                | User ID, phone, carrier                             | SIM Swap                                | Async               |
| **Compliance**             | Event: FraudConfirmed            | Full case data                                      | AML/Sanctions intersection              | Async               |
| **Notifications**          | Event: FraudAlertRaised          | Alert data (no PII)                                 | All types                               | Async               |
| **Customer Support**       | API: GET /cases, POST /appeal    | Case data, appeal data                              | User Appeals                            | Yes                 |
| **Finance (Provisioning)** | API: GET /fraud-stats            | Fraud loss, recovery, rates                         | IFRS 9 Loss Provisioning                | Async (daily batch) |

## Detailed Integration Contracts

### 1. Wallet Module → FraudEngine

**Trigger:** Every wallet transfer (P2P, cash-in, cash-out)

```php
// In Wallet module's TransferService:
class TransferService {
    public function execute(TransferRequest $request): TransferResult
    {
        // 1. Validate balance
        // 2. Check limits

        // 3. Fraud screening (SYNC — must complete < 200ms)
        $fraudResult = FraudEngineFacade::screen(
            new ScreenTransactionRequest(
                featureSource: 'wallet',
                transactionId: $transaction->id,
                amount: $request->amount,
                currency: 'SYP',
                senderId: $request->sender_id,
                recipientId: $request->recipient_id,
                context: [
                    'device_fingerprint' => $request->device_fingerprint,
                    'device_name' => $request->device_name,
                    'device_os' => $request->device_os,
                    'location' => $request->location,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'transaction_type' => $request->type,
                ],
                senderProfile: [
                    'account_age_days' => $sender->created_at->diffInDays(),
                    'kyc_level' => $sender->kyc_level,
                    'avg_transaction_amount' => $sender->avgTransactionAmount(30),
                    'transaction_count_30d' => $sender->transactionCount(30),
                    'total_volume_30d' => $sender->totalVolume(30),
                    'risk_tier' => $sender->risk_tier,
                ],
                recipientProfile: [
                    'account_age_days' => $recipient->created_at->diffInDays(),
                    'kyc_level' => $recipient->kyc_level,
                    'trust_score' => $recipient->trust_score,
                ],
            )
        );

        // 4. Act on fraud decision
        return match ($fraudResult->decision) {
            'approve' => $transaction->complete(),
            'verify' => $transaction->requireVerification(),
            'review' => $transaction->markForReview(),
            'block' => throw new FraudBlockedException($fraudResult),
        };
    }
}
```

### 2. Agent Module → FraudEngine

**Trigger:** Every agent transaction (cash-in, cash-out, balance check)

**Additional agent context:**

```php
$fraudResult = FraudEngineFacade::screen(
    featureSource: 'agent',
    // ... standard fields ...
    context: [
        'agent_id' => $agent->id,
        'agent_float' => $agent->current_float,
        'agent_expected_float' => $agent->expected_float,
        'agent_trust_score' => $agent->trust_score,
        'agent_dispute_rate' => $agent->disputeRate(30),
        'agent_location' => $agent->location,
        'agent_transaction_count_today' => $agent->todayTransactionCount(),
        'customer_id' => $customer->id,
        // ... device, location, etc.
    ],
);
```

**Agent-specific fraud rules triggered:**

- AGT-012: Agent Float Variance
- AGT-013: Rapid In/Out to Same Recipient
- AGT-014: Transaction Volume Spike
- AGT-015: No Customer at Location
- AGT-016: Agent Personal Account Receiving
- AGT-017: Commission Ratio Anomaly

### 3. Remittance Module → FraudEngine

**Trigger:** Incoming remittance from diaspora sender to Syria recipient

**Additional remittance context:**

```php
$fraudResult = FraudEngineFacade::screen(
    featureSource: 'remittance',
    // ... standard fields ...
    context: [
        'sender_country' => 'Germany',    // Diaspora location
        'sender_currency' => 'EUR',
        'amount_eur' => 300,
        'recipient_sim_changed_hours_ago' => $recipient->simChangedHoursAgo(),
        'recipient_device_fingerprint' => $recipient->currentDevice(),
        'corridor_risk_score' => CorridorRisk::get('EUROPE_SYRIA'),
        'remittance_type' => 'diaspora_support',
    ],
);
```

**Remittance-specific rules:**

- SIM-001: SIM Change + Remittance
- RMT-001: Corridor High Risk
- RMT-002: Recipient New Account
- RMT-003: Amount Mismatch (SYP vs EUR)

### 4. Merchant Module → FraudEngine

**Trigger:** Every merchant payment

**Additional merchant context:**

```php
$fraudResult = FraudEngineFacade::screen(
    featureSource: 'merchant',
    context: [
        'merchant_id' => $merchant->id,
        'merchant_trust_score' => $merchant->trust_score,
        'merchant_refund_rate_30d' => $merchant->refundRate(30),
        'merchant_device_fingerprint' => $merchant->deviceFingerprint,
        'customer_device_fingerprint' => $customer->currentDevice(),
        'devices_match' => $merchant->deviceFingerprint === $customer->currentDevice(),
        'customer_merchant_txn_count' => $customer->txnCountWithMerchant($merchant->id, 30),
    ],
);
```

### 5. Payroll Module → FraudEngine

**Trigger:** Bulk salary disbursement by employer

```php
$fraudResult = FraudEngineFacade::screenBatch(
    featureSource: 'payroll',
    transactionId: $batch->id,
    amount: $batch->total,
    senderId: $employer->id,
    recipients: $batch->employees->pluck('wallet_id')->toArray(),
    context: [
        'employer_id' => $employer->id,
        'employee_count' => $batch->employees->count(),
        'new_employees_count' => $batch->employees->filter(fn($e) => $e->created_at->gt(now()->subDays(30)))->count(),
        'total_amount' => $batch->total,
        'average_amount' => $batch->total / $batch->employees->count(),
        'payroll_cycle' => 'monthly',
        'previous_payroll_amount' => $employer->previousPayrollAmount(),
    ],
);
```

### 6. Compliance Module ← FraudEngine

**Trigger:** Fraud confirmed (confirmed_fraud state)

```php
// FraudEngine emits to Compliance
event(new FraudConfirmed(
    case: $fraudCase,
    amount: $fraudCase->amount,
    fraudType: $fraudCase->fraudType,
    victimUser: $fraudCase->victim,
    suspectUser: $fraudCase->suspect,
    evidence: $fraudCase->evidence,
));
```

**Compliance takes:**

- If amount > 1M SYP → auto-generate SAR
- If involved parties on sanctions list → notify CBS immediately
- Add to quarterly fraud statistics

### 7. Notification Module ← FraudEngine

**Trigger:** Various fraud events

```php
// P0 alert → immediate push + SMS + Slack
event(new FraudAlertRaised(
    priority: 'P0',
    type: 'account_takeover',
    message: 'Account takeover detected for user 8492. Amount: 500,000 SYP.',
    channels: ['slack', 'push', 'sms'],
));

// False positive → apology notification
event(new FraudFalsePositive(
    transactionId: $txn->id,
    channels: ['push', 'sms'],
));
```

## Integration Sequence Diagram

```
Wallet Module          FraudEngine             Compliance         Notifications
    │                      │                      │                   │
    │──TransactionInitiated                     │                   │
    │─────────────────────▶│                     │                   │
    │                      │                     │                   │
    │                      │──Screen────────────▶│  (scoring)        │
    │                      │◀─Decision───────────│                   │
    │                      │                     │                   │
    │◀───Decision──────────│                     │                   │
    │                      │                     │                   │
    │  (continue/hold)     │                     │                   │
    │                      │                     │                   │
    │                      │──FraudAlertRaised──────────────────────▶│
    │                      │                     │                   │
    │                      │                     │──FraudConfirmed──▶│
    │                      │                     │  (if > 1M SYP)    │
    │                      │                     │                   │
    │  (case resolved)     │                     │                   │
    │                      │                     │──SAR filed──▶CBS │
    │                      │                     │                   │
```

## API Contracts Summary

| Endpoint                        | Method       | Source                | Destination | Format   | SLA     |
| ------------------------------- | ------------ | --------------------- | ----------- | -------- | ------- |
| POST /fraud/screen              | Internal API | Any feature           | FraudEngine | JSON     | < 200ms |
| POST /fraud/screen-batch        | Internal API | Payroll               | FraudEngine | JSON     | < 500ms |
| GET /fraud/cases                | REST API     | Customer Support      | FraudEngine | JSON     | < 500ms |
| POST /fraud/cases/{id}/decision | Internal API | FraudEngine → Actions | Various     | Event    | < 100ms |
| GET /fraud/reports/cbs          | REST API     | Compliance            | FraudEngine | JSON/PDF | < 5s    |
| GET /fraud/reports/provisioning | REST API     | Finance               | FraudEngine | JSON     | < 5s    |
| GET /fraud/health               | Internal API | Monitoring            | FraudEngine | JSON     | < 50ms  |
