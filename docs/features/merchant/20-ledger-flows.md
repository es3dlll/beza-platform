# Merchant Ledger Flows

## Account Structure

### Chart of Accounts (Merchant-Specific)
| Code | Account Name | Type | Normal Balance |
|------|-------------|------|---------------|
| 1201 | Merchant Settlement Clearing | Asset | Debit |
| 1202 | Merchant MDR Receivable | Asset | Debit |
| 3104 | Beza MDR Income (QR) | Revenue | Credit |
| 3105 | Beza MDR Income (POS) | Revenue | Credit |
| 3106 | Beza MDR Income (Link) | Revenue | Credit |
| 3107 | Beza MDR Income (Web) | Revenue | Credit |
| 5102 | Merchant Pending Settlement | Liability | Credit |

### Journal Entry Patterns

#### QR Payment (Gross)
```
QR Payment (45,000 SYP, static QR)
Timestamp: 2026-06-01T10:30:00Z
Reference: TXN-MER-ABC123

DR  1101  Customer SYP Wallets (Customer)        45,000
CR  1201  Merchant Settlement Clearing            45,000
-- Customer debited, merchant settlement credited (gross)
```

#### Daily Settlement
```
Daily Settlement for Merchant #42
Timestamp: 2026-06-02T00:15:00Z
Reference: SETTLE-42-2026-06-01

Step 1: Transfer net to merchant wallet
DR  1201  Merchant Settlement Clearing           835,385
CR  1101  Customer SYP Wallets (Merchant #42)    835,385
-- Net settlement transferred to merchant's wallet

Step 2: Recognize MDR revenue
DR  1202  Merchant MDR Receivable                 14,615
CR  3104  Beza MDR Income (QR)                     7,155
CR  3105  Beza MDR Income (POS)                    5,260
CR  3106  Beza MDR Income (Link)                   2,200
-- MDR revenue recognized by payment method

Step 3: Clear settlement account
DR  5102  Merchant Pending Settlement             850,000
CR  1201  Merchant Settlement Clearing            850,000
-- Settlement clearing account zeroed
```

#### Refund
```
Full Refund of QR Payment (45,000 SYP)
Timestamp: 2026-06-02T14:00:00Z
Reference: REF-MER-ABC123

Step 1: Reverse original credit to merchant
DR  1201  Merchant Settlement Clearing            45,000
CR  1101  Customer SYP Wallets (Customer)         45,000
-- Customer re-credited

Step 2: Reverse MDR (if refund within settlement window)
DR  3104  Beza MDR Income (QR)                       675
CR  1202  Merchant MDR Receivable                     675
-- MDR reversed (net 0)
```

#### Payment Link
```
Payment Link Paid (45,000 SYP, 2.0% MDR)
Timestamp: 2026-06-01T11:00:00Z
Reference: TXN-MER-DEF456

DR  1101  Customer SYP Wallets (Customer)        45,000
CR  1201  Merchant Settlement Clearing            45,000
-- Same structure as QR, different MDR rate applied at settlement
```

#### POS Terminal Transaction
```
POS Sale (120,000 SYP, 2.0% MDR)
Timestamp: 2026-06-01T10:45:00Z
Reference: TXN-MER-GHI789

DR  1101  Customer SYP Wallets (Customer)       120,000
CR  1201  Merchant Settlement Clearing           120,000
-- Same structure, MDR rate 2.0% applied at settlement
```

## Daily Reconciliation (Merchant)

```
Daily Merchant Reconciliation (02:00 AM):
1. Merchant Settlement Balance Check:
   SELECT SUM(gross_amount - net_amount) FROM merchant_settlements WHERE date = TODAY
   vs
   SELECT SUM(mdr_amount) FROM merchant_transactions WHERE date = TODAY AND settled = true
   → Must match

2. Transaction Count Check:
   SELECT COUNT(*) FROM merchant_transactions WHERE date = TODAY AND status = 'completed'
   vs
   SELECT SUM(transaction_count) FROM merchant_settlements WHERE period_start = TODAY
   → Must match

3. MDR Rate Check:
   For each merchant, verify MDR applied = expected MDR rate × gross
   Alert if deviation > 0.01%

4. Settlement Completion Check:
   All merchants with transactions today have settlement record
   Alert if any merchant has incomplete settlement by 01:00 AM

Alert if any check fails → Slack #ops-finance
```

## Refund Ledger Flow

```
Refund within settlement window (< 24h, same day):
  DR  Merchant Settlement Clearing      amount
  CR  Customer Wallet                   amount
  DR  Beza MDR Income                   mdr_amount
  CR  Merchant MDR Receivable           mdr_amount
  → Net effect: Reversal with no financial impact to Beza

Refund after settlement (> 24h, next day):
  DR  Merchant Wallet                   amount
  CR  Customer Wallet                   amount
  → Merchant's wallet is debited directly
  → MDR is NOT refunded (settlement already paid out)
  → Merchant bears the full refund cost
```
