# Merchant AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the Merchant Acquiring feature. Follow these specifications precisely.

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

### 1. Create Merchants Table
```php
// database/migrations/2026_01_01_001001_create_merchants_table.php
// Schema definition in 16-database-schema.md
// Fields: id, user_id, tenant_id, business_name, business_type (enum),
//         license_number, license_verified, shop_photos (json),
//         location (POINT), customer_phone, status, tier, mdr_*_rate,
//         settlement_period, webhook_url, webhook_secret, webhook_events (json),
//         daily_txn_limit, monthly_txn_limit, per_txn_max, per_txn_min,
//         referral_code, referred_by, metadata (json), timestamps, soft_deletes
// Indexes: user_id, tenant, status, tier, business_type, referral_code
// Spatial index: location
// Foreign keys: user_id → users.id, tenant_id → tenants.id
```

### 2. Create Merchant QR Codes Table
```php
// database/migrations/2026_01_01_001002_create_merchant_qr_codes_table.php
// Fields: id, merchant_id, type (static/dynamic), amount (nullable),
//         qr_data, image_url, status, scan_count, expires_at, timestamps
// Indexes: merchant_id, status, type
```

### 3. Create Merchant Payment Links Table
```php
// database/migrations/2026_01_01_001003_create_merchant_payment_links_table.php
// Fields: id, uuid (unique), merchant_id, amount, currency, description,
//         status (pending/paid/expired/cancelled), paid_at, paid_by,
//         transaction_id, short_url, expires_at, timestamps
// Indexes: merchant_id, status, expires_at, uuid
```

### 4. Create Merchant POS Terminals Table
```php
// database/migrations/2026_01_01_001004_create_merchant_pos_terminals_table.php
// Fields: id, merchant_id, terminal_id, serial_number (unique), model,
//         certificate_sn (unique), certificate_pem (text, encrypted),
//         last_paired_at, last_seen_at, status, firmware_version,
//         metadata (json), timestamps
// Indexes: merchant_id, status, serial_number
```

### 5. Create Merchant Transactions Table
```php
// database/migrations/2026_01_01_001005_create_merchant_transactions_table.php
// Fields: id, tenant_id, merchant_id, wallet_transaction_id, customer_id,
//         customer_phone, method (qr/payment_link/pos/web_checkout),
//         qr_id, payment_link_id, pos_terminal_id, amount, mdr_rate,
//         mdr_amount, net_amount (generated), currency, status,
//         reference, cfe_reference, settled, settled_at, settlement_id,
//         refunded_at, refund_reason, metadata (json), timestamps
// Indexes: merchant_id, method, status, created_at, settlement_id, customer_id
```

### 6. Create Merchant Settlements Table
```php
// database/migrations/2026_01_01_001006_create_merchant_settlements_table.php
// Fields: id, merchant_id, period_start, period_end, gross_amount,
//         mdr_amount, net_amount, currency, transaction_count,
//         cfe_reference, status (pending/processing/completed/failed),
//         paid_at, failure_reason, metadata (json), timestamps
// Indexes: merchant_id, status, period_start, period_end
```

### 7. Create Merchant Webhook Deliveries Table
```php
// database/migrations/2026_01_01_001007_create_merchant_webhook_deliveries_table.php
// Fields: id, merchant_id, event_type, payload (json), url,
//         status (pending/delivered/failed/cancelled), attempt_count,
//         max_attempts, last_attempt_at, last_response_code,
//         last_response_body (text), next_retry_at, signature, completed_at, timestamps
// Indexes: merchant_id, status, next_retry_at, event_type
```

## Model Files to Create

### Merchant Model
```php
// app/Modules/Merchant/Models/Merchant.php
// Relations: user(), qrCodes(), paymentLinks(), posTerminals(),
//            transactions(), settlements(), webhookDeliveries()
// Scopes: verified(), pending(), active(), byTier(), byType()
// Methods: isVerified(), isActive(), canAcceptPayment(), getMdrRate(),
//          getPublicProfile(), calculateTier()
// Casts: business_type (BusinessType enum), status (MerchantStatus enum),
//         tier (MerchantTier enum), settlement_period (SettlementPeriod enum),
//         shop_photos (array), metadata (array), webhook_events (array)
```

### MerchantTransaction Model
```php
// app/Modules/Merchant/Models/MerchantTransaction.php
// Relations: merchant(), qrCode(), paymentLink(), posTerminal(),
//            walletTransaction(), settlement()
// Scopes: completed(), byMethod(), byMerchant(), forDate(), unsettled()
// Methods: isRefundable(), calculateMdr(), markSettled()
// Casts: method (PaymentMethod enum), status (TransactionStatus enum),
//         currency (Currency enum)
```

## Service Implementation Notes

### MerchantService
```php
// constructor injection: MerchantRepository, QrService, CfeService, EventService
// register(): Create merchant → generate static QR → create CFE account → emit event
// verify(): Update status → emit MerchantVerified
// getByUser(): Single merchant per user
// Suspension/holds are handled by separate methods
```

### QrService
```php
// generateStaticQr(): Build payload → generate image with logo → upload to CDN → save record
// generateDynamicQr(): Same but with amount + optional expiry
// serveQrImage(): Increment scan count → redirect to CDN URL (302)
// buildQrPayload(): format: beza://pay/merchant/{id}?type={type}[&amount={amount}]
// QR generated at 400x400px, stored as PNG on CDN
```

### PaymentLinkService
```php
// create(): Validate amount → generate UUID → build short URL → save → return
// processPayment(): Validate link is payable → call transfer service → mark paid → emit event
// cancel(): Cancel if still pending
// expireStaleLinks(): Cron job to expire links past their expires_at
// Short URL format: https://pay.beza.com/pay/{uuid}
```

### SettlementService
```php
// processDailySettlements(): For each active merchant → calculate → create → post CFE → emit
// processMerchantSettlement(): Calculate gross, MDR, net per merchant
// MDR calculated per-transaction based on method-specific rate
// CFE posting: DR settlement clearing, CR merchant wallet
// Generate settlement report (PDF) on completion
```

## Flutter Implementation Notes

### Screens to Build
1. **MerchantHomeScreen**: Daily sales card, quick actions, recent transactions, settlement preview
2. **QrDisplayScreen**: Large QR code, brightness boost, download/share, amount toggle
3. **PaymentLinkScreen**: Create link form, preview, share sheet
4. **TransactionHistoryScreen**: Filterable paginated list with search
5. **TransactionDetailScreen**: Amount, MDR breakdown, refund option
6. **SettlementHistoryScreen**: Daily settlement list with detail
7. **MerchantRegistrationFlow**: Multi-step registration (phone → OTP → PIN → business info → docs)

### State Management
- Use Riverpod with code generation (@riverpod annotation)
- Main providers: merchantDashboardProvider, merchantQrProvider, paymentLinkFormProvider,
  merchantTransactionListProvider, merchantSettlementProvider

### Key Widgets
- SalesAnimatedCounter: Animated number for daily sales (counts up on new payment)
- QrCodeImageWidget: High-res QR render with logo overlay
- MdrBadge: Small badge showing MDR rate and method
- SettlementProgressBar: Animated progress toward EOD settlement
- BrightnessBoostButton: Sets screen to max brightness for 60 seconds

### Voice Integration
- Play sound + speak amount on payment received
- Use flutter_tts for Arabic text-to-speech
- Enable/disable in settings

### Offline Support
- QR images cached locally (never need internet to display)
- Last known sales cached in SQLite
- Transaction history cached (last 200)
- Pending payment links: created online only

## Testing Requirements
- Minimum 80% code coverage on services
- All API endpoints have 200, 400, 401, 403, 422 response tests
- QR generation tests: static, dynamic, with logo, without logo
- Payment link tests: create, pay, expired, double-pay prevention
- Settlement tests: daily batch, mixed MDR rates, empty merchant, failure
- E2E tests for: QR payment flow, payment link flow, merchant registration
- Flutter widget tests for all screens (loading, empty, error, success states)
