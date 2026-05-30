# Core Financial Engine V2

## Architecture
The CFE is the central nervous system of Beza. Every financial transaction passes through CFE regardless of feature origin.

## Sub-Engines
| Engine | Responsibility | Key Method |
|--------|---------------|------------|
| Account Engine | Manages user accounts per currency | `getOrCreate(userId, currency)` |
| Balance Engine | Tracks available/held/reserved balances | `getBalance(accountId)` |
| Hold Engine | Places and releases holds | `hold(accountId, amount, reason)` → `release(holdId)` |
| Posting Engine | Records double-entry journal entries | `post(debitEntry, creditEntry)` |
| Fee Engine | Calculates fees per transaction type/tier | `calculate(txnType, amount, userTier)` |
| FX Engine | Resolves and locks exchange rates | `resolve(pair, amount, context)` → `lock(rateId, txnId)` |
| Settlement Engine | Manages settlement lifecycle | `settle(batchId)` → `release(batchId)` |
| Reserve Engine | Manages regulatory reserve requirements | `checkReserve(currency)` |
| Reversal Engine | Reverses completed transactions | `reverse(originalTxnId, reason)` |
| Liquidity Engine | Monitors and forecasts liquidity | `forecast(currency, horizon)` |

## Transaction Lifecycle (State Machine)
```
                     ┌──────────────────────────────┐
                     │        Transaction Init       │
                     │  Available → Held (reserved)  │
                     └──────────────┬───────────────┘
                                    │
                         ┌──────────▼──────────┐
                         │     Authorization    │
                         │  Validate rules/limits│
                         └──────────┬──────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
            ┌───────▼───────┐              ┌───────▼───────┐
            │   Approved    │              │   Rejected    │
            │   Hold Locked │              │   Hold→Avail  │
            └───────┬───────┘              └───────────────┘
                    │
            ┌───────▼───────┐
            │    Posting    │
            │  Debit/Credit │
            └───────┬───────┘
                    │
            ┌───────▼───────┐
            │  Settlement   │
            │  Net/Clear    │
            └───────┬───────┘
                    │
            ┌───────▼───────┐
            │  Completed    │
            │  Event Emit   │
            └───────┬───────┘
                    │
          ┌─────────┴──────────┐
          │                    │
    ┌─────▼─────┐       ┌─────▼─────┐
    │  Reversed │       │  Expired  │
    │  Rollback │       │  Auto-rls │
    └───────────┘       └───────────┘
```

## Hold Engine Detail
```
States:
  Available  → Held     (pending authorization)
  Available  → Blocked  (compliance hold)
  Held       → Posted   (authorized + settled)
  Held       → Released (failed/expired/cancelled)
  Held       → Disputed (customer dispute)
  Disputed   → Posted   (resolved in favor)
  Disputed   → Released (resolved against)

Methods:
  hold(accountId, amount, reason, expiresAt) → HoldId
  release(holdId, reason) → bool
  capture(holdId) → TransactionId
  expire(holdId) → bool
  getActiveHolds(accountId) → Hold[]
  getHoldSummary(accountId) → { held, available, total }
```

## Ledger Integration
Every CFE posting creates a double-entry journal entry:
```
Example: P2P Transfer of 10,000 SYP

DR: Sender Wallet    10,000 SYP
CR: Recipient Wallet  9,850 SYP
CR: Beza Fee Income     150 SYP

Accounts affected: sender_wallet, recipient_wallet, fee_income
```

## CFE API (Internal — Service-to-Service)
```json
POST /internal/cfe/hold
{ "account_id": "acc_123", "amount": 10000, "currency": "SYP",
  "reason": "transfer", "expires_in_minutes": 30,
  "idempotency_key": "txn_abc_123" }
→ { "hold_id": "hold_456", "status": "held", "held_at": "2026-06-01T10:00:00Z" }

POST /internal/cfe/release
{ "hold_id": "hold_456", "reason": "transfer_completed" }
→ { "status": "released", "released_at": "2026-06-01T10:00:05Z" }

POST /internal/cfe/post
{ "hold_id": "hold_456", "entries": [
    { "account_id": "sender_wallet", "type": "debit", "amount": 10000 },
    { "account_id": "recipient_wallet", "type": "credit", "amount": 9850 },
    { "account_id": "fee_income", "type": "credit", "amount": 150 }
  ], "reference": "txn_abc_123" }
→ { "posting_id": "post_789", "status": "posted" }
```
