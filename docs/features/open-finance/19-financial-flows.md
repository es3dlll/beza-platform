# Open Finance Financial Flows

## Flow 1: Payment Initiation (API → Wallet)

### Step-by-Step
```
Step 1: Developer calls POST /v1/of/payments
Step 2: API Gateway authenticates API key
Step 3: RateLimitService checks limits
Step 4: IdempotencyService checks idempotency key
Step 5: Payment validation:
  - Amount between 1,000 and 10,000,000 SYP
  - Recipient phone number exists on Beza
  - Source has sufficient balance (developer's funding wallet)
Step 6: CFE Transfer Execution:
  - Debit developer funding wallet: 25,050 SYP (25,000 + 50 fee)
  - Credit recipient wallet: 25,000 SYP
  - Credit Beza Fee Income: 50 SYP (0.2% API fee)
Step 7: Webhook delivery: payment.completed
Step 8: Log to api_usage_logs
Step 9: Return PaymentResult to developer

Double-Entry:
  DR  Developer Funding Wallet    25,050 SYP
  CR  Recipient Wallet            25,000 SYP
  CR  Beza API Fee Income             50 SYP
  Reference: PAY-ABC123XYZ
```

### Sequence Diagram
```
Developer App      API Gateway      OpenFinanceService      WalletService      Recipient
    │                   │                   │                    │                 │
    │─ POST /payments ──>│                   │                    │                 │
    │                   │─ Auth API Key ───>│                    │                 │
    │                   │─ Rate Check ─────>│                    │                 │
    │                   │─ Idempotency ────>│                    │                 │
    │                   │                   │─ InitiatePayment ─>│                 │
    │                   │                   │                    │─ CFE Transfer ─>│
    │                   │                   │                    │<─ Confirmed ────│
    │                   │                   │<─ PaymentResult ──│                 │
    │                   │                   │─ emit(PaymentComp)│                 │
    │                   │                   │─ WebhookDelivery ─│── POST ────────>│
    │                   │                   │─ LogUsage ───────>│                 │
    │<─── 201 Created ──│<─── Response ─────│                    │                 │
```

## Flow 2: Bulk Payment Disbursement (NGO Use Case)

```
Step 1: NGO uploads batch of 1,000 payments via POST /v1/of/payments/bulk
Step 2: System validates:
  - Total amount <= developer wallet balance
  - All phone numbers in valid format
  - No duplicate reference numbers
Step 3: Total debit: 5,000,000 SYP (1,000 × 5,000) + 10,000 SYP fees (0.2%)
Step 4: Hold entire amount from developer wallet
Step 5: Process individual payments asynchronously (100/sec)
Step 6: Per-payment webhook: payment.completed or payment.failed
Step 7: Summary webhook: bulk.completed with counts, total succeeded, total failed
Step 8: Release hold, update developer wallet with net change

Revenue:
  API Fee: 0.2% × 5,000,000 = 10,000 SYP
```

## Flow 3: OAuth Token Issuance

```
Step 1: Developer registers OAuth client (client_id + client_secret)
Step 2: Client app requests: POST /v1/of/oauth/token
  grant_type=client_credentials
  client_id=beza_client_abc123
  client_secret=cs_live_xyz...
Step 3: OAuthService validates client credentials
Step 4: Access token generated (random 64-char string)
Step 5: Token stored with SHA-256 hash
Step 6: Token returned (expires in 2 hours)
Step 7: Developer uses token in Authorization header

Revenue model:
  - Token issuance is free (encourages usage)
  - Revenue comes from transaction fees on API calls
```

## Flow 4: Sandbox Testing Cycle

```
Step 1: Developer receives sandbox key on registration
Step 2: Sandbox has simulated:
  - 1,000,000 SYP test balance
  - 10,000 USD test balance
  - Test phone numbers: +963900000001..100
  - Pre-seeded transaction history (50 sample txns)
Step 3: Developer makes test payment → simulated engine processes
Step 4: Webhook inspector shows simulated event
Step 5: Developer can reset sandbox (POST /v1/of/sandbox/reset)
Step 6: Reset restores initial state, clears transaction log

Revenue model:
  - Sandbox is free (developer acquisition)
  - Conversion to paid tier when ready for production
```
