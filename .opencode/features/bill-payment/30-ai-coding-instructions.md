# Bill Payment AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the Bill Payment feature. Follow these specifications precisely.

## Implementation Order
```
Phase 1 (Files 1-10): Database migrations + Models + Enums
Phase 2 (Files 11-20): Repositories + Services + Actions
Phase 3 (Files 21-30): Controllers + API routes + Policies
Phase 4 (Files 31-40): Events + Listeners + Jobs
Phase 5 (Files 41-50): Biller Integrations (one per biller)
Phase 6 (Files 51-60): Tests + Factories
Phase 7 (Files 61-70): Flutter screens + Providers + Widgets
```

## Migration Files to Create

### 1. Create Billers Table
```php
// database/migrations/2026_01_01_000010_create_billers_table.php
// Schema definition in 16-database-schema.md
// Seed with 9 Syrian billers (see seed data in schema)
// Fields: id, tenant_id, type (unique), name_ar, name_en, category,
//         interface_type (api/csv/manual), config (json), customer_id_format,
//         customer_id_example, customer_id_length, supports_fetch, supports_pay,
//         supports_status_check, supports_auto_pay, supports_partial_pay,
//         fee_percentage, fee_fixed, status, display_order, logo_url, timestamps
```

### 2. Create Bill Transactions Table
```php
// database/migrations/2026_01_01_000011_create_bill_transactions_table.php
// Fields: id, tenant_id, user_id, wallet_id, biller_id, customer_id, customer_name,
//         invoice_number, billing_period, bill_amount, late_fee, fee, fee_vat,
//         total (generated), currency, reference (unique), biller_reference,
//         status (pending/paid/failed/refunded/disputed), failure_reason,
//         paid_at, receipt_url, cfe_reference, cfe_hold_id, cfe_posting_id,
//         idempotency_key, wallet_balance_before, wallet_balance_after,
//         device_id, ip_address, metadata (json), refunded_at, refund_reason, timestamps
// Indexes: user_id, biller_id, status, created_at, customer_id, reference,
//          biller_reference, idempotency_key, paid_at, tenant_id+created_at
```

### 3. Create Scheduled Bills Table
```php
// database/migrations/2026_01_01_000012_create_scheduled_bills_table.php
// Fields: id, tenant_id, user_id, biller_id, customer_id, amount (nullable),
//         schedule_type (once/monthly/bi_monthly/quarterly), reminder_days,
//         reminder_method (push/sms/both), next_due, auto_pay_enabled,
//         auto_pay_status (nullable), auto_pay_failures, last_error,
//         last_reminded_at, status (active/paused/cancelled/completed),
//         cancelled_at, timestamps
```

### 4. Create Biller Connection Logs Table
```php
// database/migrations/2026_01_01_000013_create_biller_connection_logs_table.php
// Partitioned by month
// Fields: id, tenant_id, biller_id, biller_type, operation (fetch/pay/status_check/confirm),
//         customer_id, request_url, request_body (json), response_body (json),
//         http_status, success, error_message, duration_ms, created_at
```

### 5. Create CSV Batch Tables
```php
// database/migrations/2026_01_01_000014_create_csv_batch_files_table.php
// Fields: id, tenant_id, biller_id, filename, original_name, file_size,
//         total_records, processed_records, failed_records,
//         status (uploaded/processing/ready/completed/failed), error_message,
//         processed_at, timestamps

// database/migrations/2026_01_01_000015_create_csv_billable_items_table.php
// Fields: id, csv_batch_file_id, customer_id, reference, amount, due_date,
//         fee_type, ministry, university, semester, metadata (json),
//         status (pending/available/paid/expired), paid_at, bill_transaction_id
```

## Model Files to Create

### Biller Model
```php
// app/Modules/BillPayment/Models/Biller.php
// Relations: transactions(), scheduledBills(), connectionLogs(), batchFiles()
// Scopes: active(), byCategory(), api(), csv()
// Methods: isActive(), calculateFee(int $amount), getConfig(string $key)
// Casts: config (array), category (BillerCategory enum), interface_type (BillerInterfaceType enum), status (BillerStatus enum)
```

### BillTransaction Model
```php
// app/Modules/BillPayment/Models/BillTransaction.php
// Relations: user(), biller(), wallet()
// Scopes: paid(), failed(), pending(), today(), thisMonth(), byBiller(), byCustomerId()
// Methods: canRefund(), markRefunded(string $reason)
// Casts: status (BillTransactionStatus enum), metadata (array)
```

### ScheduledBill Model
```php
// app/Modules/BillPayment/Models/ScheduledBill.php
// Relations: user(), biller()
// Scopes: active(), dueForReminder(), dueForAutoPay(), overdue()
// Methods: isDueForReminder(Carbon $now), isDueForAutoPay(Carbon $now)
// Casts: schedule_type (ScheduleType enum), status (ScheduleStatus enum)
```

## Service Implementation Notes

### BillPaymentService
```php
// constructor injection: BillerProviderService, WalletService, FeeService, CfeService,
//   TransactionRepository, ReceiptService, EventService
//
// fetchBill() method:
//   1. Validate customer ID format (BillerValidationService)
//   2. Get biller integration (BillerProviderService::getBiller)
//   3. Fetch from biller (BillerInterface::fetchBill)
//   4. Log connection (BillerConnectionLog)
//   5. Anomaly check (AI service if configured)
//   6. Return BillDTO
//
// payBill() method:
//   1. Validate PIN (WalletService::verifyPin)
//   2. Re-fetch bill (prevent stale data)
//   3. Verify bill not already paid
//   4. Calculate fee (FeeService::calculateBillPaymentFee)
//   5. Check wallet balance (CfeService::checkSufficientBalance)
//   6. Execute CFE hold (CfeService::hold)
//   7. Confirm with biller (BillerInterface::confirmPayment)
//      → On failure: release hold, throw exception
//   8. Execute CFE post (CfeService::post)
//   9. Persist BillTransaction record
//   10. Generate receipt (ReceiptService::generate)
//   11. Emit BillPaid, WalletDebited events
//   12. Return PaymentResult with receipt
```

### BillerProviderService
```php
// Manages all biller integrations via BillerFactory
// Methods:
//   getBiller(string $billerType): BillerInterface
//   getAllActiveBillers(): Collection
//   getBillersByCategory(string $category): Collection
//   validateCustomerId(string $billerType, string $customerId): bool
//   getCustomerIdFormat(string $billerType): array
```

### BillingScheduler
```php
// processDueReminders() — hourly cron
//   Find scheduled_bills where next_due - reminder_days = today
//   Send push/SMS notification per user preference
//   Update last_reminded_at
//
// processAutoPay() — daily cron at 08:00
//   Find scheduled_bills where next_due = today AND auto_pay_enabled = true
//   For each: fetch bill → check balance → pay
//   On success: update next_due, emit AutoPayCompleted
//   On failure: increment auto_pay_failures, emit AutoPayFailed
//   After 3 failures: pause auto-pay, notify user
```

### ReceiptService
```php
// generate(BillTransaction $transaction, BillDTO $bill): Receipt
//   Build receipt data array
//   Render Blade template to PDF (Barryvdh\DomPDF or similar)
//   Store PDF on S3
//   Update transaction.receipt_url
//   Return Receipt DTO
```

## Biller Integration Pattern

### PeedElectricityBiller (Example)
```php
// app/Modules/BillPayment/Integrations/PeedElectricityBiller.php
// implements BillerInterface
//
// Config from billers table:
//   base_url: https://api.peed.gov.sy/v1
//   timeout: 15 seconds
//   retry_count: 3
//   api_key + hmac_secret from Vault
//
// fetchBill(string $customerId): BillDTO
//   POST /bill/inquiry { customer_id, timestamp, signature }
//   Validate response format
//   Map to BillDTO
//
// confirmPayment(string $customerId, string $amount, string $reference, array $extra): PaymentResultDTO
//   POST /payment/confirm { customer_id, amount, beza_reference, timestamp, signature }
//   Validate biller response
//   Return PaymentResultDTO with biller_reference
//
// checkStatus(string $billerReference): StatusCheckDTO
//   GET /payment/status/{billerReference}
//   Return status
//
// getBillerType(): string → 'peed'
// supportsFeature(string $feature): bool → check config
```

### SyriatelBiller (Example)
```php
// SOAP/XML integration with Syriatel B2B API
// Client certificate authentication (mTLS)
// XML request/response parsing
// Same BillerInterface contract
```

### GovernmentFeesBiller (CSV-based)
```php
// No real-time API — reads from csv_billable_items table
// fetchBill(string $customerId): BillDTO
//   Query csv_billable_items WHERE customer_id = $customerId AND status IN ('pending','available')
//   If found: return BillDTO with items as breakdown
//   If not found: throw BillNotFoundException
//
// confirmPayment(...): PaymentResultDTO
//   Update csv_billable_items SET status = 'paid', bill_transaction_id = ...
//   Return reference based on batch file reference
```

## FeeService Implementation
```php
// calculateBillPaymentFee(int $billAmount, string $billerType): int
//   Get biller config from billers table
//   Percentage: ceil($billAmount * $biller->fee_percentage / 100)
//   Plus fixed: $biller->fee_fixed
//   Return sum
```

## Flutter Implementation Notes

### State Management
- Use Riverpod with code generation (@riverpod annotation)
- Five main providers: BillCategoryProvider, BillFetchProvider, BillPaymentProvider, BillHistoryProvider, BillScheduleProvider
- Implement optimistic updates for payment (show processing immediately)
- Event bus integration for receipt notifications and balance updates

### Screens
1. BillCategoryScreen: Grid of 6 categories (3 columns) → biller list → customer ID entry
2. CustomerIdEntryScreen: Biller header + customer ID input (auto-format, validation) + fetch button
3. BillDetailScreen: Hero amount + breakdown table + late fee banner + confirm + PIN
4. PaymentResultScreen: Success animation + receipt card + share/PDF buttons
5. BillHistoryScreen: Filter tabs + search + paginated list
6. ScheduledBillsScreen: Overdue section + upcoming section + add reminder button

### Offline Support
- Biller catalog cached in SQLite (synced daily)
- Recent customer IDs cached per biller (last 10 per biller)
- Payment queue for offline pays (sync when online)
- Receipts cached locally for 90 days

## Testing Requirements
- Minimum 80% code coverage on BillPaymentService, FeeService, BillingScheduler
- All API endpoints have 200, 400, 401, 403, 422, 502 response tests
- Integration tests for: PEED fetch, PEED pay, Syriatel pay, government CSV pay
- E2E tests for: pay electricity bill, set reminder, view history
- Biller mock for each integration (HTTP fake for API billers, in-memory for CSV)
- Flutter widget tests for: BillCategoryScreen, CustomerIdEntryScreen, BillDetailScreen, PaymentResultScreen
