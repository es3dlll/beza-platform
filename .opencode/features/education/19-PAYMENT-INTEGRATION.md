# 19 — Payment Integration

## 19.1 Payment Flow Architecture

```
Parent App                     Education Service           Payment Core           School
     │                              │                         │                    │
     │  POST /payments/create       │                         │                    │
     │─────────────────────────────>│                         │                    │
     │                              │                         │                    │
     │                              │ Validate invoice        │                    │
     │                              │ Check balance           │                    │
     │                              │ Lock invoice            │                    │
     │                              │                         │                    │
     │                              │ POST /wallet/debit      │                    │
     │                              │────────────────────────>│                    │
     │                              │                         │                    │
     │                              │ Debit parent wallet     │                    │
     │                              │ <────────────────────────│                    │
     │                              │                         │                    │
     │  Receipt ◄───────────────────│                         │                    │
     │                              │                         │                    │
     │                              │ Publish event:          │                    │
     │                              │ payment.completed       │                    │
     │                              │─────────────────────────┼───────────────────>│
     │                              │                         │  Webhook:          │
     │                              │                         │  Payment received  │
```

## 19.2 Supported Payment Methods

| Method | Source | Fee to School | Settlement |
|---|---|---|---|
| Beza Wallet Balance | Parent's Beza wallet | 2.5% | T+1 to bank account |
| Visa/Mastercard | Any bank card | 3.0% (includes card fee) | T+2 |
| Bank Transfer (SADAD) | Syrian banks | 1.5% | T+0 (instant via SADAD) |
| Offline Cash | Record at school | 0% (recorded manually) | N/A |
| Diaspora Wallet (EUR/USD/SAR) | Beza Remittance | 2.5% + FX spread | T+1 in SYP or USD |
| Auto-Pay | Beza Wallet | 2.5% | T+1 |

## 19.3 FX Conversion for Diaspora Payments

```
Parent (EUR Wallet)           FX Engine             Education Service          School (SYP)
     │                            │                        │                       │
     │ Pay 4.8M SYP invoice       │                        │                       │
     │───────────────────────────>│                        │                       │
     │                            │                        │                       │
     │                            │ Get rate: SYP/EUR      │                       │
     │                            │ Rate: 25,890           │                       │
     │                            │ Amount: €185.36        │                       │
     │                            │<──────────────────────│                       │
     │                            │                        │                       │
     │ Confirm €185.36            │                        │                       │
     │<───────────────────────────│                        │                       │
     │                            │                        │                       │
     │ Confirm                    │                        │                       │
     │───────────────────────────>│                        │                       │
     │                            │ Reserve EUR 185.36     │                       │
     │                            │ Convert to SYP 4.8M    │                       │
     │                            │ Credit school account  │                       │
     │                            │───────────────────────────────────────────────>│
     │ Receipt                    │                        │                       │
     │<───────────────────────────│                        │                       │
```

## 19.4 Settlement to Schools

| Frequency | Default | Configurable |
|---|---|---|
| Daily (batch) | Yes — all payments settled at 23:00 | Can request instant settlement |
| Weekly | Optional for low-volume schools | Via dashboard |
| Monthly | Optional | Via dashboard |

### Settlement Components
- **Gross payments**: sum of all completed payments in period
- **Transaction fees**: Beza's 2.5% fee
- **Card processing fees**: passed through at cost
- **Net settlement**: gross - fees = amount transferred

## 19.5 Refund Flow

1. School initiates refund from dashboard (select payment → Refund)
2. Beza ops approves (or auto-refund if 24h duplicate detection)
3. Payment Core debits school's escrow/pending balance
4. Parent wallet is credited
5. Receipt invalidated, new receipt generated with "REFUNDED"
6. Both parent and school receive notification

## 19.6 Reconciliation

- Every payment has a unique `payment_reference` and `idempotency_key`
- Daily reconciliation report generated for each school
- Beza ops runs global reconciliation against Payment Core ledger
- Failed/disputed transactions flagged for manual review
- End-of-term bulk reconciliation with school's own records
