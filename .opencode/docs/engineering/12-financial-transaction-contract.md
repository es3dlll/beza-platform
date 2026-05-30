# Financial Transaction Contract

> Every financial operation in the system must conform to this contract.
> Violation = non-negotiable PR rejection.

---

## 1. Transaction Lifecycle

```
                 ┌─────────────┐
                 │  VALIDATE   │
                 │  (KYC,limits│
                 │  fraud,hold)│
                 └──────┬──────┘
                        │
                 ┌──────▼──────┐
                 │    HOLD     │
                 │  (if needed)│
                 └──────┬──────┘
                        │
                 ┌──────▼──────┐
                 │     FEE     │
                 │  (if any)   │
                 └──────┬──────┘
                        │
                 ┌──────▼──────┐
                 │   POSTING   │
                 │  (CFE ->    │
                 │   Ledger)   │
                 └──────┬──────┘
                        │
                 ┌──────▼──────┐
                 │  COMPLETE   │
                 │  (event,    │
                 │  notify)    │
                 └─────────────┘
```

## 2. Operation Types

| Type | Debit Account | Credit Account | Fee Trigger | Hold Required |
|------|--------------|----------------|-------------|---------------|
| `wallet_deposit` | User Wallet | Settlement | No | No |
| `wallet_withdrawal` | Settlement | User Wallet | Yes | Yes |
| `wallet_transfer` | Sender Wallet | Receiver Wallet | Maybe | Yes |
| `bill_payment` | User Wallet | Biller Account | Yes | Yes |
| `agent_cash_in` | Agent Wallet | User Wallet | No | No |
| `agent_cash_out` | User Wallet | Agent Wallet | Yes | Yes |
| `fx_conversion` | Source Wallet | Target Wallet | Yes | Yes |
| `fee_charge` | User Wallet | Income Account | N/A | No |
| `reversal` | Reversed | Reversed | No | No |
| `settlement` | Various | Settlement Account | No | No |

## 3. Data Contract

Every financial transaction MUST include:

```json
{
  "reference_type": "wallet_transfer",
  "reference_id": "01AR...",          // ULID
  "description": "Transfer from X to Y",
  "channel": "mobile_app | web | agent | ussd | api",
  "initiated_by": "user_id | agent_id | system",
  "idempotency_key": "req_...",       // Prevents double processing
  "metadata": {
    "wallet_id": "...",
    "sender_phone": "963...",
    "receiver_phone": "963...",
    "kyc_tier": 2,
    "device_id": "..."
  },
  "lines": [
    {
      "account_id": "...",
      "amount": 50000,
      "type": "debit",
      "description": "..."
    },
    {
      "account_id": "...",
      "amount": 50000,
      "type": "credit",
      "description": "..."
    }
  ]
}
```

## 4. Validation Rules (Pre-Posting)

Every transaction MUST pass:

1. **Active account check** — Both accounts exist and are active
2. **Currency match** — Both accounts have same currency (`Rate` object used if different)
3. **Sufficient balance** — Available balance >= debit amount + fees
4. **KYC tier check** — Operation allowed for user's KYC tier
5. **Daily limit check** — User hasn't exceeded daily limits
6. **Fraud check** — Velocity + amount thresholds
7. **Idempotency check** — `reference_type` + `reference_id` not already processed
8. **Double-entry balance** — Total debits == Total credits

## 5. Post-Posting Requirements

After successful posting:

1. **Event** — emit domain event (e.g., `WalletCredited`)
2. **Notification** — push/SMS to involved parties
3. **Audit log** — write to `cfe_transactions` + `journal_entries`
4. **Cache invalidation** — clear balance cache for affected wallets

## 6. Reversal Contract

To reverse a transaction:

1. Call `ReversalEngine::reverse()` with original `reference_id`
2. System creates reversed journal entry (credit becomes debit, vice versa)
3. Original entry marked as reversed in metadata
4. Fee is NOT reversed automatically (separate process)

```php
$reversal = new ReversalInstructionDto(
    originalTransactionId: '01AR...',
    reason: 'duplicate_transaction',
    initiatedBy: 'support_user_id',
);
$result = $reversalEngine->reverse($reversal);
```

## 7. Fee Assessment Rules

| Scenario | Fee Type | Charged To | Revenue Account |
|----------|----------|------------|-----------------|
| External transfer | `transfer_out` | Sender | `4000-001` |
| Cash withdrawal (agent) | `agent_cash_out` | User | `4000-005` |
| Bill payment | `bill_payment` | User | `4000-003` |
| FX conversion | `fx_conversion` | User | `4000-006` |
| Wallet-to-wallet | `wallet_to_wallet` | Free | N/A |

Fee rules are DB-driven (`fee_rules` table) and modifiable via admin panel.
