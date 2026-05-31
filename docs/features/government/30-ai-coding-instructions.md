# Government Collections AI Coding Instructions

## Module Overview
You are implementing the **Government Collections** feature for Beza Platform — a comprehensive system for Syrian citizens to pay all government fees digitally: taxes, vehicle registration, passport fees, court fees, municipality fees, university tuition, and civil registry fees. Arabic-first, integration-first with Syrian government ministries.

## Key Architecture Decisions

1. **Feature-first Laravel module** under `app/Modules/GovernmentCollect/`
2. **Ministry adapter pattern** — each ministry has its own `MinistryAdapter` implementation; new ministries added by creating a new adapter class
3. **Queuing mode** — if ministry API is down, payments are queued (not rejected) and processed when ministry comes back online
4. **Idempotency is mandatory** — every payment endpoint must support `Idempotency-Key` header; duplicate payments must be impossible
5. **Receipt-first design** — every payment generates an official government receipt with QR code; receipt must be verifiable independently
6. **Guest payments supported** — users can pay government fees without a full Beza account (limited to 500K SYP per transaction)
7. **Reconciliation is critical** — daily automated reconciliation between Beza and each ministry; any variance >0.1% flags for human review

## Naming Conventions

### Laravel (PHP)
- Models: `GovernmentBiller`, `GovernmentTransaction`, `GovernmentReceipt`, `GovernmentReconciliation`
- Services: `GovPaymentGatewayService`, `TaxPaymentService`, `FinePaymentService`, `TuitionPaymentService`, `InvoiceService`, `ReconciliationService`
- Actions: `QueryTaxAction`, `PayTaxAction`, `QueryFineAction`, `GenerateReceiptAction`, `RunReconciliationAction`
- Controllers: `TaxController`, `FineController`, `PassportController`, `TuitionController`, `ReceiptController`, `ReconciliationController`
- Integrations (adapters): `MinistryOfFinanceAdapter`, `MinistryOfInteriorAdapter`, `TrafficAuthorityAdapter`, `UniversityPortalAdapter`
- Events: `GovernmentPaymentInitiated`, `GovernmentPaymentCompleted`, `GovernmentPaymentFailed`, `GovernmentReceiptGenerated`, `GovernmentReconciliationCompleted`
- Jobs: `ProcessMinistrySettlement`, `RunDailyReconciliation`, `SyncMinistryStatuses`, `RetryFailedPayment`, `NotifyUpcomingDeadline`
- Requests: `TaxQueryRequest`, `TaxPayRequest`, `FineQueryRequest`, `PassportPayRequest`, `TuitionPayRequest`
- Resources: `GovernmentTransactionResource`, `GovernmentReceiptResource`, `GovernmentBillerResource`

### Flutter (Dart)
- Screens: `GovernmentHubScreen`, `TaxQueryScreen`, `TaxPaymentScreen`, `PassportPaymentScreen`, `TuitionPaymentScreen`, `PaymentHistoryScreen`, `ReceiptScreen`
- Providers: `GovernmentHubProvider`, `TaxPaymentProvider`, `FinePaymentProvider`, `PassportPaymentProvider`, `PaymentHistoryProvider`, `ReceiptProvider`
- Models: `TaxObligationModel`, `FineObligationModel`, `PassportApplicationModel`, `GovernmentReceiptModel`, `GovernmentTransactionModel`
- Use Cases: `QueryTaxUseCase`, `PayTaxUseCase`, `QueryFineUseCase`, `PayPassportUseCase`, `GetPaymentHistoryUseCase`, `GenerateReceiptUseCase`

## API Standards

### Response Format
```json
{
  "status": "success" | "error",
  "data": { ... },
  "error": {
    "code": "ERROR_CODE",
    "message": "Arabic message",
    "details": { ... }
  }
}
```

### Error Codes
```
VALIDATION_ERROR           — Input validation failed
TAX_ID_NOT_FOUND           — Tax ID not in ministry records
INSUFFICIENT_BALANCE       — Wallet balance insufficient
MINISTRY_QUERY_FAILED      — Ministry API query error
MINISTRY_UNAVAILABLE       — Ministry API down
MINISTRY_TIMEOUT           — Ministry API timeout
DUPLICATE_PAYMENT          — Idempotency key already used
PAYMENT_FAILED             — Payment processing failed
RECEIPT_NOT_FOUND          — Receipt reference not found
RECEIPT_REVOKED            — Receipt has been revoked
SETTLEMENT_FAILED          — Settlement to ministry failed
RECONCILIATION_MISMATCH    — Payment not matching ministry record
```

### Idempotency
- All POST financial endpoints MUST support `Idempotency-Key` header (UUID)
- Store `idempotency_key` in `government_transactions.idempotency_key` (unique index)
- On duplicate: return 200 with existing transaction data AND `"duplicate": true` flag
- Idempotency keys expire after 24 hours

### Pagination (History)
- `GET /government/history?page=1&per_page=20&sort=created_at&order=desc`
- Max `per_page`: 100, default: 20
- Filters: `service`, `biller`, `from`, `to`, `status`, `min_amount`, `max_amount`

## Database Rules

### Insert-Only Transactions
```php
// CORRECT: Create new record, never update transaction amount/status once recorded
GovernmentTransaction::create([...]);

// CORRECT: Status transitions append new status (status_log JSON)
$txn->status = 'completed';
$txn->status_log = array_merge($txn->status_log ?? [], [
    ['status' => 'completed', 'at' => now()->toIso8601String()]
]);
$txn->save();

// WRONG: Never delete or modify past transaction amounts
GovernmentTransaction::where('id', $id)->update(['amount' => 999]); // NEVER
```

### Receipt Integrity
```php
// Receipt hash = SHA-256 of: receipt_ref + total_paid + currency + ministry_reference + created_at
$hash = hash('sha256', implode('|', [
    $receiptRef,
    $totalPaid,
    $currency,
    $ministryReference ?? '',
    $generatedAt,
]));
```

## Ministry Adapter Contract

```php
interface MinistryAdapter
{
    /**
     * Query obligations for a given reference ID
     * @throws MinistryConnectionException
     */
    public function queryObligations(string $referenceId): QueryResult;

    /**
     * Confirm payment to ministry
     * @throws MinistryTimeoutException
     */
    public function confirmPayment(PaymentConfirmation $confirmation): ConfirmationResult;

    /**
     * Check status of a previously confirmed payment
     */
    public function checkStatus(string $referenceId): StatusResult;

    /**
     * Settle batch of payments (end of day)
     */
    public function settleBatch(array $transactions): SettlementResult;

    /**
     * Get adapter health status
     */
    public function health(): HealthStatus;
}
```

## Ministry-Specific Integration Notes

### Ministry of Finance (MoF)
- SOAP API at `https://tax.gov.sy/ws/tax-service` (legacy) + REST API in development
- Tax ID format: 10 digits (Syrian tax number)
- Query returns: taxpayer name (partial), obligations by year, penalties
- Authentication: client certificate + HMAC signature
- Response time: 500ms–5s (variable)
- Known issue: certificate expires every 6 months — monitor expiry

### Ministry of Interior (MoI) — Passport
- REST API at `https://moi.gov.sy/api/passport/v1`
- Application number format: PPR-YYYY-NNNNNNN
- Authentication: API key + Basic Auth
- Supports both standard and urgent fee types
- Real-time confirmation required (not batch)

### Traffic Directorate
- REST API at `https://traffic.gov.sy/api/v1`
- Fine query by: licence plate (Arabic-Indic digits) or driver licence number
- Early payment discount: 50% within 90 days of fine issuance
- Settlement: weekly file-based (SFTP)

## Receipt Generation
```php
// Receipt QR data format:
// beza://verify?ref=GOV-YYYY-MMDD-XXXX&hash=sha256..&t=timestamp

// Verification URL:
// https://api.beza.sy/api/v1/government/receipts/GOV-YYYY-MMDD-XXXX/verify

// Verification returns:
// - receipt_ref, amount, biller, status (valid/revoked), ministry_confirmation
```

## Currency Handling
- All amounts stored in smallest unit (SYP piasters: 1 SYP = 1 unit)
- Display formatting: Arabic-Indic digits with thousands separator
- `number_format($amount, 0, '.', ',')` → English digits; use custom Arabic formatter for display

## Payment Flow State Machine
```
State Machine per Transaction:
  initiated → pending_ministry → completed → settled
                                      ↓
                                   failed → (retry up to 3 times)
                                      ↓
                                   refunded (if retries exhausted)
```
