# Cards Financial Flows

## Flow 1: Card Authorization (Online Purchase)

### Step-by-Step
```
Step 1: Auth Request from Merchant
  Merchant (AliExpress) submits ISO 8583 0100 auth request
  → PAN: 639123XXXXXX4567
  → Amount: 125,000 SYP
  → MCC: 5311 (ecommerce)
  → Country: CN
  → CVV: 123
  → Expiry: 12/28

Step 2: Route & BIN Lookup
  CardBINService: BIN 639123 → local switch routing
  Check: Card exists and is active ✓
  Check: Card not expired ✓

Step 3: Fraud Check
  CardFraudService: Score = 12/100
  Rules checked:
    - Velocity (3 txns in 5 min on same card): 1 txn → PASS
    - Geographic anomaly: Card issued SY, merchant CN → Online purchase, expected → PASS
    - Amount vs user history: 125K vs avg 80K → within 2σ → PASS
    - BIN attack pattern: Single auth, not part of batch → PASS
  Action: APPROVE (score < 40)

Step 4: Limit Check
  CardLimitService:
    Category: online
    Limit: 500,000 SYP
    Spent today: 75,000 SYP
    Requested: 125,000 SYP
    Remaining: 425,000 SYP → APPROVE

Step 5: Balance Check
  Hold on Card Wallet (SYP):
    Available: 600,000 SYP
    Hold: 125,000 SYP
    Available after: 475,000 SYP → APPROVE

Step 6: Auth Response (ISO 8583 0110)
  Response code: 00 (Approved)
  Auth code: AUTH-ABC123
  RRN: RRN-987654
  STAN: 123456

Step 7: Record Authorization
  INSERT INTO card_transactions (status=authorized, amount=125000, ...)

Step 8: Emit Event
  CardTransactionAuthorized(card_id=1, amount=125000, merchant=AliExpress)

Step 9: Notify User
  Push: "تم الدفع 125,000 ل.س في AliExpress - البطاقة 1234"

Step 10: Settlement (End of Day)
  Local switch sends clearing file (ISO 8583 0420)
  CardProcessor: batch clearing → match auths → calculate fees
  Post to CFE: DR Card Wallet 125,000 CR Merchant Account 123,500 CR Beza Fee Income 1,500
```

### Sequence Diagram (Text)
```
Merchant          Switch          CardProcessor         CFE          User App
   │                │                  │                 │              │
   │── Auth 0100 ──>│                  │                 │              │
   │                │── Auth Request──>│                 │              │
   │                │                  │── BIN Lookup ──>│              │
   │                │                  │<── BIN OK ─────│              │
   │                │                  │                 │              │
   │                │                  │── Fraud Check ─>│              │
   │                │                  │<── Score 12 ───│              │
   │                │                  │                 │              │
   │                │                  │── Limit Check ─>│              │
   │                │                  │<── Limits OK ──│              │
   │                │                  │                 │              │
   │                │                  │── Hold ────────>│              │
   │                │                  │<── Hold OK ────│              │
   │                │                  │                 │              │
   │                │<── Auth 0110 ────│                 │              │
   │<── Auth 0110 ──│                  │                 │              │
   │                │                  │── Record Auth ─>│              │
   │                │                  │── Emit Event ──>│              │
   │                │                  │                 │── Push Notif─>│
   │                │                  │                 │              │
   │                │                  │   [EOD Batch]   │              │
   │                │── Clearing ─────>│                 │              │
   │                │                  │── Post to CFE ─>│              │
   │                │                  │<── Post OK ────│              │
   │                │                  │── Release Hold ─>│              │
```

## Flow 2: Virtual Card Creation

### Step-by-Step
```
Step 1: User taps "Create Card"
  App: POST /api/v1/cards/create
  Body: {type: "virtual", currency: "SYP", limits: {online: 500000, ...}}

Step 2: Validate Requirements
  Check: KYC Level >= 2 ✓
  Check: Card limit per user not exceeded (max 5 virtual cards) ✓
  Check: Sufficient wallet balance for fee (5,000 SYP) ✓

Step 3: Assign BIN
  CardBINService.assignBIN(type=virtual, currency=SYP)
  → BIN: 639123
  → Next PAN: 6391230000000123 (incremented from counter)
  → Sequence: next_available → next_available + 1

Step 4: Generate PAN Components
  PAN: 6391230000000123
  Expiry: 12/28 (4 years from issue month)
  CVV: Generated via HSM (3-digit, track 2 equivalent)
  PIN: Not set yet (first-time PIN prompt on activation)

Step 5: Store Card Record
  INSERT INTO cards (bin=639123, pan_hash=SHA256(PAN), last_four=0123,
                     expiry=12/28, type=virtual, status=active, ...)

Step 6: Create Card Wallet
  Create dedicated card wallet linked to card
  Transfer fee: 5,000 SYP from main wallet → card wallet
  Available balance for spending: user-specified initial funding

Step 7: Emit Event
  CardCreated(card_id=N, user_id=42, type=virtual, bin=639123)

Step 8: Prepare for Wallet Addition
  TokenizationService: Prepare card data for Apple Pay/Google Pay
  If user chooses "Add to Wallet":
    → TSP creates DPAN (device PAN)
    → DPAN stored in card_tokens table
    → Wallet pass added to device

Step 9: Notify User
  Push: "بطاقتك الافتراضية جاهزة! رقم 0123"
```

### Sequence Diagram (Text)
```
User App          CardService       BINService        HSM           DB
   │                  │                │              │             │
   │── Create Card ──>│                │              │             │
   │                  │── Validate ───>│              │             │
   │                  │<── OK ────────│              │             │
   │                  │                 │              │             │
   │                  │── Assign BIN ──>│              │             │
   │                  │<── PAN + BIN ──│              │             │
   │                  │                 │              │             │
   │                  │── Generate CVV────────────────>│             │
   │                  │<── CVV ────────────────────────│             │
   │                  │                 │              │             │
   │                  │── Save Card ────────────────────────────────>│
   │                  │<── Saved ────────────────────────────────────│
   │                  │                 │              │             │
   │                  │── Emit Event ──>│              │             │
   │<── Card Ready ───│                 │              │             │
   │                  │                 │              │             │
   │── Add to Wallet ─>│                 │              │             │
   │                  │── Tokenize ────>│              │             │
   │<── Wallet Added ─│                 │              │             │
```

## Flow 3: ATM Withdrawal (Physical Card)

### Step-by-Step
```
Step 1: Card Insertion
  User inserts physical card at ATM
  ATM reads chip (EMV) → card authentication via SDA/DPA
  ATM terminal: POST ISO 8583 0100 auth request

Step 2: PIN Verification
  ATM prompts for PIN → user enters 6-digit PIN
  PIN block encrypted (ISO 9564 format 0) → sent to HSM
  HSM verifies PIN vs stored PIN block
  3 attempts max → on 3rd failure, card blocked for 24h

Step 3: Authorization
  CardProcessor.authorize():
    Check: Card active ✓
    Check: Card not frozen ✓
    Check: ATM category limit (200,000 SYP)
    Request: 50,000 SYP → within limit ✓
    Check: Sufficient balance (hold on card wallet)
    Fraud check: ATM withdrawal from user's city → expected location

Step 4: Hold on Wallet
  CFE: Hold 50,000 SYP on card wallet
  Status: Available → 550,000 → Available 500,000 (held 50,000)

Step 5: Auth Response
  Response code: 00 (Approved)
  Auth code: AUTH-ATM-789
  Dispense instruction sent to ATM

Step 6: Cash Dispensed
  ATM dispenses 50,000 SYP
  ATM sends completion advice (ISO 8583 0220)

Step 7: Record Transaction
  INSERT INTO card_transactions (type=atm, amount=50000, status=authorized, ...)

Step 8: Settlement (EOD)
  ATM acquirer sends batch settlement
  Match auth with completion
  Post to CFE: hold → settled (DR Card Wallet 50,000)
  Calculate fee: 2,000 SYP + 0.5% (250 SYP) = 2,250 SYP
  Post fee: DR Card Wallet 2,250 CR Beza Fee Income 2,250

Step 9: Notify User
  SMS: "تم السحب 50,000 ل.س من الصراف - الرصيد: 447,750 ل.س"
```

### Sequence Diagram (Text)
```
User       ATM        Switch       HSM        CardProcessor       CFE
 │          │           │          │              │               │
 │─Insert──>│           │          │              │               │
 │─Enter PIN─>           │          │              │               │
 │          │─Auth 0100─>│          │              │               │
 │          │           │──Verify──>│              │               │
 │          │           │<──OK─────│              │               │
 │          │           │──Auth───────────────>│               │
 │          │           │           │              │               │
 │          │           │           │              │──Check Status │
 │          │           │           │              │──Check Limit  │
 │          │           │           │              │──Fraud Check  │
 │          │           │           │              │               │
 │          │           │           │              │──Hold ───────>│
 │          │           │           │              │<──Hold OK ────│
 │          │           │           │              │               │
 │          │<──Auth 0110───────│              │               │
 │          │─Dispense─>│           │              │               │
 │<─Cash 50K─│           │           │              │               │
 │          │           │           │              │               │
 │          │─Advice ──>│           │              │               │
 │          │           │──Record──────────────>│               │
 │          │           │           │              │──Emit Event ──>│
 │          │           │           │              │               │
 │          │           │           │   [EOD]      │               │
 │          │           │           │              │──Settle ─────>│
 │          │           │           │              │<──Done ──────│
 │<──SMS────│           │           │              │               │
```
