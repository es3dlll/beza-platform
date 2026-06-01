# 04 - علاقات الجداول

```
┌──────────────────┐        ┌─────────────────────────────────────────┐
│    merchants      │───────>│       merchant_settlements              │
│──────────────────│ 1     M│─────────────────────────────────────────│
│ id               │        │ PK id                                   │
└──────────────────┘        │ FK merchant_id                          │
                            │ amount (المبلغ قبل الرسوم)              │
                            │ fee_percentage (2%)                     │
                            │ transfer_fee (1%)                       │
                            │ refunds_deducted                        │
                            │ net_amount (المبلغ الصافي)              │
                            │ currency                                │
                            │ status (pending/processing/completed/   │
                            │         failed)                         │
                            │ bank_account_info (JSON)                │
                            │ bank_transaction_ref                    │
                            │ settlement_date                         │
                            │ created_at                              │
                            └─────────────────────────────────────────┘
```
