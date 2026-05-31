# Wallet Financial Flows

## Flow 1: P2P Transfer (SYP)

### Step-by-Step
```
Step 1: Hold
  Account: Sender Main Wallet (SYP)
  Amount: 25,125 SYP (transfer 25,000 + fee 125)
  State: Available → Held
  Reason: "Pending P2P Transfer to +963912345678"
  Expires: 30 minutes

Step 2: Authorize
  Check: Sufficient balance ✓
  Check: Daily limit (475,000 used + 25,125 = 500,125 < 500,000 → FAIL)
    → Actually, assume 400,000 used → 425,125 < 500,000 ✓
  Check: Fraud score (12/100 → allow)
  Check: Recipient exists and active

Step 3: Post (Double-Entry)
  DR: Sender Main Wallet      25,125 SYP
  CR: Recipient Main Wallet   25,000 SYP
  CR: Beza Fee Income Account    125 SYP
  Reference: TXN-ABC123XYZ

Step 4: Release Hold
  Hold ID: hold_456 → Released
  Reason: "Transfer completed TXN-ABC123XYZ"

Step 5: Update Balances
  Sender: 100,000 → 74,875 SYP (after: 100,000 - 25,125)
  Recipient: 50,000 → 75,000 SYP (after: 50,000 + 25,000)
  Fee Account: 0 → 125 SYP

Step 6: Emit Events
  - WalletDebited(sender, 25,125)
  - WalletCredited(recipient, 25,000)
  - TransferSent(transaction)
```

### Sequence Diagram (Text)
```
Sender App          API Gateway         TransferService         CFE             Recipient
    │                    │                    │                  │                 │
    │── POST send ──────>│                    │                  │                 │
    │                    │── Validate Auth ──>│                  │                 │
    │                    │                    │── Check Limits ─>│                 │
    │                    │                    │<── Limits OK ────│                 │
    │                    │                    │                  │                 │
    │                    │                    │── Hold ─────────>│                 │
    │                    │                    │<── Hold OK ─────│                 │
    │                    │                    │                  │                 │
    │                    │                    │── Post Entries ─>│                 │
    │                    │                    │<── Post OK ─────│                 │
    │                    │                    │                  │                 │
    │                    │                    │── Release Hold ─>│                 │
    │                    │                    │                  │                 │
    │                    │                    │── Save TXN ─────>│                 │
    │                    │                    │── Emit Events ──>│                 │
    │                    │                    │                  ├── Notify ──────>│
    │<── Response ───────│<── 200 OK ────────│                  │                 │
    │                    │                    │                  │                 │
```

## Flow 2: Cash-in via Agent

```
Step 1: User requests cash-in at agent (100,000 SYP)
Step 2: Agent creates cash-in request on POS
Step 3: User verifies with PIN on POS or via USSD
Step 4: System checks:
  - Agent has sufficient float (200,000 ≥ 100,000 ✓)
  - User wallet is active ✓
  - User within max balance (500,000 limit, current 150,000 + 100,000 = 250,000 ✓)
Step 5: Debit agent float account: 100,000 SYP
Step 6: Credit user wallet: 100,000 SYP
Step 7: Credit Beza Fee Income: 0 (cash-in is free for user)
Step 8: Agent earns commission: 1,000 SYP (1% of 100,000)
  → Credit Agent Commission Account: 1,000 SYP
Step 9: Emit events
```

## Flow 3: Intra-currency (USD)

```
Similar to Flow 1 but:
  - Separate USD wallet for sender and recipient
  - No FX conversion needed
  - Fees structured differently (0.5% capped at $5)
  - CFE uses USD account engine
```

## Flow 4: Cross-currency (SYP → USD)

```
Step 1: Sender holds 10,000,000 SYP, wants to send $500 USD
Step 2: FX Engine:
  - Rate: 1 USD = 12,500 SYP (Beza rate: mid-market 12,400 + 0.8% spread)
  - Amount in SYP: 500 × 12,500 = 6,250,000 SYP
  - Fee: 0.5% of 500 USD = $2.50 USD equivalent = 31,250 SYP
  - Total debit: 6,281,250 SYP
Step 3: Hold SYP sender account: 6,281,250 SYP
Step 4: FX Lock: Rate 12,500, expires in 60 seconds
Step 5: Debit SYP account: 6,281,250 SYP
Step 6: Credit USD recipient account: $500 USD
Step 7: Credit Beza FX Income: 31,250 SYP ($2.50 spread + $2.50 fee)
Step 8: Release holds
```
