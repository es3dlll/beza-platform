# Cards AI Coding Instructions

## Instructions for AI Code Generation Agent

This file contains the exact instructions for an AI coding agent to implement the Cards feature. Follow these specifications precisely.

## Implementation Order
```
Phase 1 (Files 1-10): Database migrations + Models + Enums
Phase 2 (Files 11-20): Repositories + Services + Actions
Phase 3 (Files 21-30): Controllers + API routes + Policies
Phase 4 (Files 31-40): Events + Listeners + Jobs
Phase 5 (Files 41-50): Integrations (Switch, HSM, TSP, Bureau)
Phase 6 (Files 51-60): Tests + Factories
Phase 7 (Files 61-70): Flutter screens + Providers + Widgets
```

## Migration Files to Create

### 1. Create Cards Table
```php
// database/migrations/2026_06_01_000001_create_cards_table.php
// Schema definition in 16-database-schema.md
// Fields: id, user_id, tenant_id, bin, pan_hash, pan_suffix, expiry,
//         card_type (enum:virtual,physical), card_network (enum:mastercard,visa,local_scheme),
//         status (enum:active,frozen,closed,lost,expired), issuer_id, card_program,
//         currency (enum:SYP,USD), limits (json), kyc_level_at_issue, nickname,
//         metadata (json), spent_today, spent_today_at, last_used_at, issued_at,
//         activated_at, closed_at, lost_at, timestamps, soft_deletes
// Indexes: user_id, tenant, status, type, bin, unique(pan_hash)
// Foreign key: user_id → users.id, tenant_id → tenants.id
```

### 2. Create Card Transactions Table
```php
// database/migrations/2026_06_01_000002_create_card_transactions_table.php
// Schema definition in 16-database-schema.md
// Fields: id, card_id, tenant_id, uuid (unique), type (enum:purchase,atm,refund,fee,reversal),
//         amount, fee, tip, currency, billing_currency, fx_rate, original_amount,
//         merchant_name, merchant_category, merchant_country, merchant_city, merchant_id,
//         status (enum:authorized,settled,declined,refunded,reversed), decline_reason,
//         auth_code, rrn, stan, local_txn_time, auth_response, card_present,
//         chip_transaction, contactless, online_auth, recurring, tokenized, eci,
//         fraud_score, settled_at, reversal_at, timestamps
// Indexes: card_id, tenant, status, created_at, auth_code, rrn, stan
// Partition by: RANGE (YEAR(created_at) * 100 + MONTH(created_at))
// Foreign key: card_id → cards.id
```

### 3. Create Card Pins Table
```php
// database/migrations/2026_06_01_000003_create_card_pins_table.php
// Schema definition in 16-database-schema.md
// Fields: id, card_id (unique), pin_hash, pin_attempts, last_attempt_at, blocked_until, pin_changed_at, timestamps
// Foreign key: card_id → cards.id
```

### 4. Create Card Tokens Table
```php
// database/migrations/2026_06_01_000004_create_card_tokens_table.php
// Schema definition in 16-database-schema.md
// Fields: id, card_id, token (unique), token_expires, device_id, device_name,
//         wallet_type (enum:apple_pay,google_pay), status (enum:active,revoked,suspended),
//         tsp_reference, last_used_at, revoked_at, timestamps
// Indexes: card_id, status, device_id
// Foreign key: card_id → cards.id
```

### 5. Create Card BINs Table
```php
// database/migrations/2026_06_01_000005_create_card_bins_table.php
// Seed with initial BIN ranges:
//   639123 — local scheme, virtual, SYP
//   639124 — local scheme, physical, SYP
//   639125 — local scheme, virtual, SYP (one-time)
//   512345 — mastercard, both, USD (international sponsor)
//   512346 — mastercard, virtual, USD (premium international)
```

### 6. Create Card Spending Totals Table
```php
// database/migrations/2026_06_01_000006_create_card_spending_totals_table.php
// Fields: id, card_id, category (enum:online,pos,atm,international),
//         period (enum:daily,weekly,monthly), period_start (date),
//         total_spent, transaction_count, timestamps
// Unique index: (card_id, category, period, period_start)
```

## Model Files to Create

### Card Model
```php
// app/Modules/Cards/Models/Card.php
// Relations: user(), transactions(), pin(), tokens()
// Scopes: active(), frozen(), virtual(), physical(), byCurrency()
// Methods: isActive(), canTransact(), canAuthorize($amount, $category),
//          recordSpending($amount), freeze(), unfreeze(), close(), reportLost()
// Casts: card_type (CardType enum), card_network (CardNetwork enum),
//         status (CardStatus enum), currency (Currency enum),
//         limits (array), metadata (array), spent_today (integer)
// Events: creating (hash PAN before save)
```

### CardTransaction Model
```php
// app/Modules/Cards/Models/CardTransaction.php
// Relations: card(), user() via card
// Scopes: settled(), authorized(), declined(), today(), thisMonth(),
//          byType(), byMerchant(), byCountry()
// Methods: isPurchase(), isAtm(), isRefund(), isSettled(), canReverse()
// Casts: type (TransactionType enum), status (TransactionStatus enum),
//         currency (Currency enum), amount (integer), fee (integer)
```

### CardPin Model
```php
// app/Modules/Cards/Models/CardPin.php
// Relations: card()
// Methods: isBlocked(), recordFailedAttempt(), resetAttempts()
// Properties: pin_attempts max 3, block duration 24 hours
```

### CardToken Model
```php
// app/Modules/Cards/Models/CardToken.php
// Relations: card()
// Scopes: active(), expired()
// Methods: isActive(), revoke()
// Casts: wallet_type (WalletType enum), status (TokenStatus enum)
```

## Service Implementation Notes

### CardService
```php
// constructor injection: CardRepository, CardBINService, HsmClient,
//   CardLimitService, WalletService, FeeService, EventService
// create() method:
//   1. Validate KYC level >= 2
//   2. Check card count per user (max 5 virtual, 2 physical)
//   3. Check wallet balance for issuance fee
//   4. Assign BIN via CardBINService
//   5. Generate PAN (increment next_available in card_bins)
//   6. Generate CVV via HSM
//   7. Hash PAN (SHA-256) for storage
//   8. Create card record (PAN encrypted, hash stored)
//   9. Create card wallet (transfer fee from main wallet)
//   10. Emit CardCreated event
//   11. Return CardDTO (without full PAN, with hint)

// freeze() method:
//   1. Verify card belongs to user
//   2. Set status = frozen
//   3. Revoke all active tokens (Apple Pay / Google Pay)
//   4. Emit CardFrozen event

// unfreeze() method:
//   1. Set status = active
//   2. Reactivate tokens (if not revoked permanently)
//   3. Emit CardUnfrozen event

// replace() method:
//   1. Verify reason (lost, stolen, damaged, malfunction)
//   2. Close old card (status = closed)
//   3. Create new card (same PAN, BIN, last 4, new expiry + CVV)
//   4. Link new card to same card wallet
//   5. If lost: mark old card as lost, not just closed
//   6. Charge replacement fee (10,000 SYP)
//   7. Order physical card from bureau
//   8. Emit CardCreated (new) + CardClosed (old)
```

### CardProcessor
```php
// authorize(AuthorizationRequestDTO $request): AuthorizationResponseDTO
//   1. Decrypt PAN → look up card by pan_hash
//   2. Check card status (active only)
//   3. Check card not expired
//   4. Verify CVV (via HSM if stored, or pass-through)
//   5. Check category limit (CardLimitService)
//   6. Check daily spent limit
//   7. Run fraud check (CardFraudService)
//   8. Check sufficient balance (hold on card wallet via CFE)
//   9. Generate auth code, RRN, STAN
//   10. Record transaction (status = authorized)
//   11. Emit CardTransactionAuthorized event
//   12. Return authorization response

// clearing(array $transactions): void
//   1. Match clearing records to authorized transactions
//   2. Calculate interchange fees
//   3. Mark transactions as settled
//   4. Emit CardTransactionSettled events

// settlement(SettlementBatch $batch): void
//   1. Calculate net positions (card wallet, merchant, Beza)
//   2. Post to CFE
//   3. Generate settlement report
```

### CardBINService
```php
// assignBIN(CardType $type, Currency $currency): BINAssignment
//   - Find active BIN range matching type + currency
//   - Increment next_available PAN
//   - If range exhausted: mark BIN as exhausted, alert operations
//   - Return: bin, pan, card_network

// routeTransaction(string $pan): RoutingDecision
//   - Look up BIN from first 6 digits
//   - Return: local_switch or international_sponsor
//   - Include switch/sponsor connection details

// getBINDetails(string $bin): BINDetails
//   - Return: network, issuer, program, limits template, currency
```

### CardLimitService
```php
// Methods:
//   getEffectiveLimits(Card $card, User $user): array
//     — Merges card.limits with user KYC-based caps
//     — Returns per-category effective limits
//   checkAuthorization(Card $card, string $category, int $amount): LimitCheckResult
//     — Check limit (0 = disabled), check daily spent
//     — Return approved + remaining
//   updateDailyCounter(Card $card, int $amount, string $category): void
//     — Increment spent_today and card_spending_totals
//   resetDailyIfNeeded(Card $card): void
//     — Reset spent_today if new day
```

## Flutter Implementation Notes

### State Management
- Use Riverpod with code generation (@riverpod annotation)
- CardListProvider: list of cards, loading/error states, refresh
- CardDetailProvider: single card detail with transactions
- OneTimeCardProvider: generation state with countdown timer

### Screens
1. CardsHomeScreen: PageView.builder for card carousel
2. CreateCardScreen: Stepper (type → limits → confirm → success)
3. CardDetailScreen: CardVisualWidget + action grid + transaction list
4. OneTimeCardScreen: Amount input → card reveal → countdown
5. ChangePinScreen: 3x PIN input with validation

### Secure Display
- PAN revealed only after biometric (local_auth plugin)
- CVV shown with 30-second auto-hide timer
- Cards list: show only last 4 digits
- One-time card: full PAN visible (user expects to copy)
- Screenshot detection: show warning on card detail

## Testing Requirements
- Minimum 80% code coverage on CardService, CardProcessor, CardLimitService
- All API endpoints have 200, 400, 401, 403, 422 response tests
- E2E tests for: create virtual card, freeze/unfreeze, one-time card
- Flutter widget tests for: card carousel, create card, one-time card, PIN change
- Integration test with mock ISO 8583 switch
- PCI-DSS compliance tests: verify PAN encrypted, CVV not stored, PIN not in DB
