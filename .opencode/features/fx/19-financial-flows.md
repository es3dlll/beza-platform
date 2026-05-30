# FX Engine Financial Flows

## Flow 1: Live Rate Fetch

### Step-by-Step
```
Trigger: User opens Exchange screen OR cron job (every 15s)

Step 1: RateProviderService receives request for SYP/USD
Step 2: Check Redis cache (key: fx:rate:SYP/USD)
   - Cache HIT (TTL < 15s) → return cached BezaRate
   - Cache MISS → continue to fetch

Step 3: Fetch from providers in priority order:
   Provider A (CBS Official) — priority 1
     → GET https://cbs.gov.sy/api/rates
     → Response: { "usd_buy": 12900, "usd_sell": 13300, "timestamp": "..." }
     → Success: mid = (12900 + 13300) / 2 = 13100
     → Response time: 120ms

Step 4: RateEngine calculates Beza rate:
   Source mid: 14,550 (highest priority active provider)
   User tier: Standard (3.0% spread)
   Beza rate: 14,550 × (1 + 0.03) = 14,987
   Round to: 14,990 (nearest 5)

Step 5: Cache rate in Redis (TTL 15s):
   Key: fx:rate:SYP/USD
   Value: { mid: 14550, beza_rate: 14990, bid: 14400, ask: 14700, ... }

Step 6: Persist rate to fx_rates table (partitioned by month)

Step 7: Emit RateUpdated event
   → WebSocket push to all connected clients
   → Analytics pipeline

Step 8: Return BezaRate to caller

Provider Failure Path:
   Provider A: timeout (2s) → retry → timeout → mark degraded
   Provider B: success (300ms) → use this rate
   All providers fail: return cached rate with stale=true
```

### Sequence Diagram (Text)
```
Exchange App        API Gateway        RateProviderService     Redis         Provider A      Provider B
    │                    │                    │                 │              │               │
    │── GET /fx/rates ──>│                    │                 │              │               │
    │                    │── fetchRates ─────>│                 │              │               │
    │                    │                    │── GET cache ───>│              │               │
    │                    │                    │<── MISS ───────│              │               │
    │                    │                    │                 │              │               │
    │                    │                    │── fetch(A) ───────────────────>│               │
    │                    │                    │<── timeout ───────────────────│               │
    │                    │                    │── fetch(A retry) ─────────────>│               │
    │                    │                    │<── timeout ───────────────────│               │
    │                    │                    │                 │              │               │
    │                    │                    │── fetch(B) ──────────────────────────────────>│
    │                    │                    │<── rate ─────────────────────────────────────│
    │                    │                    │                 │              │               │
    │                    │                    │── applySpread ──│              │               │
    │                    │                    │── SET cache ───>│              │               │
    │                    │                    │── save history  │              │               │
    │                    │                    │── emit event ──>│              │               │
    │                    │                    │                 │              │               │
    │<── rates ──────────│<── 200 OK ────────│                 │              │               │
    │                    │                    │                 │              │               │
```

## Flow 2: Rate Lock + Conversion

### Step-by-Step
```
Step 1: User enters conversion details:
   Source: SYP Wallet (10,000,000 SYP balance)
   Target: USD Wallet ($0 balance)
   Amount: 5,000,000 SYP
   Rate: 1 USD = 14,935 SYP (Beza rate with premium spread 1.5%)

Step 2: System validates:
   - Wallet exists and is active ✓
   - Sufficient balance: 5,000,000 ≤ 10,000,000 ✓
   - Within KYC limits: 5,000,000 ≤ 20,000,000 daily ✓
   - Source + target not same currency ✓

Step 3: Rate Lock (30s):
   Redis key: fx:lock:SYP/USD:user_42
   Lua script: ATOMIC set with EXPIRE 30
   Lock acquired: lock_abc123def456
   Rate: 14,935 (frozen for 30 seconds)
   Timer starts in UI: 30... 29... 28...

Step 4: User confirms with PIN:
   Verification: pin_hash matches stored hash ✓

Step 5: Execute conversion:
   RateLockService.useLock(lock_abc123def456)
     → Lock exists → not expired → mark as USED

Step 6: CFE Operations:
   Hold: DR Source Wallet (SYP)     5,000,000  SYP
         CR CFE Internal Clearing    5,000,000  SYP
   Post: CR Target Wallet (USD)         334.78 USD
         DR CFE Internal Clearing    5,000,000  SYP  (SYP leg)
         CR CFE Internal Clearing        334.78 USD  (USD leg)
   Release: Clear holds

Step 7: Journal Entries:
   DR  1101  Customer SYP Wallets (User)      5,000,000
   CR  1102  Customer USD Wallets (User)            334.78 USD
   -- Conversion: SYP → USD at rate 14,935
   
   DR  3102  Beza FX Income (Spread)             150,000 SYP (implied)
   -- Spread revenue from conversion (2.6% of 5,000,000)
   
   DR  5101  Settlement Clearing                5,000,000 SYP
   CR  1101  Customer SYP Wallets (User)        5,000,000
   -- SYP leg of conversion
   
   DR  1102  Customer USD Wallets (User)              334.78 USD
   CR  5101  Settlement Clearing                       334.78 USD
   -- USD leg of conversion

Step 8: Persist: fx_conversions table row

Step 9: Emit events:
   - ConversionCompleted (user_42, 5M SYP → $334.78 USD)
   - WalletDebited (SYP: 10M → 5M)
   - WalletCredited (USD: $250 → $584.78)

Step 10: Return receipt:
   Conversion ID: conv_abc123
   Reference: FX-CONV-ABC123XYZ
```

### Sequence Diagram (Text)
```
User App            API Gateway         RateLockService     RateEngine        CFE          Wallet
    │                    │                    │                 │              │             │
    │── POST /fx/lock ──>│                    │                 │              │             │
    │                    │── lockRate ───────>│                 │              │             │
    │                    │                    │── Lua SET ─────>│              │             │
    │                    │                    │<── lockId ─────│              │             │
    │                    │                    │── save lock ───>│              │             │
    │                    │                    │── emit event ──>│              │             │
    │<── lock OK ────────│<── 200 OK ────────│                 │              │             │
    │                    │                    │                 │              │             │
    │  (rate locked, 30s countdown)          │                 │              │             │
    │                    │                    │                 │              │             │
    │── POST /fx/convert>│                    │                 │              │             │
    │                    │── executeConv ────>│                 │              │             │
    │                    │                    │── useLock ─────>│              │             │
    │                    │                    │<── lock valid ─│              │             │
    │                    │                    │                 │              │             │
    │                    │                    │── CFE hold ──────────────────────────────>│    │
    │                    │                    │<── hold OK ───────────────────────────────│    │
    │                    │                    │── CFE post ───────────────────────────────>│    │
    │                    │                    │<── post OK ───────────────────────────────│    │
    │                    │                    │── save conv ──>│              │             │
    │                    │                    │── emit events ─>│              │             │
    │<── receipt ────────│<── 200 OK ────────│                 │              │             │
    │                    │                    │                 │              │             │
```

## Flow 3: Rate Arbitrage Protection

### Step-by-Step
```
Monitoring: Runs every 60s via DetectRateAnomaliesJob

Scenario: Black market suddenly spikes to 16,500 SYP/USD while
          parallel market stays at 14,550 SYP/USD

Step 1: DetectRateAnomaliesJob fetches latest rates from all providers:
   CBS Official:      13,100  SYP/USD
   Parallel Market:   14,550  SYP/USD
   Black Market:      16,500  SYP/USD  ← spike!

Step 2: Check 1 — Provider Divergence:
   Max = 16,500 (Black Market)
   Min = 13,100 (CBS Official)
   Divergence = (16,500 - 13,100) / 13,100 = 25.9% > 10% threshold
   → ANOMALY: PROVIDER_DIVERGENCE (warning)

Step 3: Check 2 — Spread Widening:
   Current spread: (16,500 - 14,550) / 14,550 = 13.4%
   Average spread (last 10 samples): 4.5%
   Ratio: 13.4 / 4.5 = 2.98x > 2x threshold
   → ANOMALY: SPREAD_WIDENING (critical)

Step 4: Check 3 — Price Spike (1-min window):
   Rate 1 min ago: 15,200 (Black Market)
   Current rate: 16,500
   Change: (16,500 - 15,200) / 15,200 = 8.6% > 5% threshold
   → ANOMALY: PRICE_SPIKE (critical)

Step 5: Actions taken:
   a. Black Market provider automatically deprioritized (priority → 99)
   b. Beza rate calculation excludes Black Market until anomaly clears
   c. Parallel Market becomes primary source
   d. Max spread limit enforced: Beza rate capped at mid + 3%
   e. Rate locked at Parallel Market rate + standard spread

Step 6: Alerts sent:
   - Slack #ops-fx: "⚠️ SYP/USD Anomaly: Black Market spike 8.6% in 1min"
   - Email to treasury team
   - Admin dashboard alert feed updated

Step 7: Audit log entry:
   action: anomaly_detected
   pair: SYP/USD
   type: PRICE_SPIKE
   severity: critical
   providers_affected: Black Market (deprioritized)
   auto_mitigation: provider_excluded, spread_capped
   timestamp: 2026-06-01T10:00:00Z
```
