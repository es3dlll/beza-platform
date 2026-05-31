# 04 - علاقات الجداول

```
┌──────────────────┐        ┌─────────────────────────────────────────┐
│    merchants      │───────>│       merchant_subscriptions            │
│──────────────────│ 1     M│─────────────────────────────────────────│
│ id               │        │ PK id                                   │
└──────────────────┘        │ FK merchant_id                          │
                            │ FK customer_id → users.id               │
                            │ amount                                  │
                            │ currency (SYP/USD)                      │
                            │ interval (monthly/yearly)               │
                            │ status (pending/active/paused/cancelled)│
                            │ max_cycles                              │
                            │ current_cycle                           │
                            │ next_charge_at                          │
                            │ customer_consented_at                   │
                            │ created_at                              │
                            └────────────────┬────────────────────────┘
                                             │ 1
                                             │ hasMany
                                             ▼
                                   ┌────────────────────┐
                                   │ subscription_charges │
                                   │────────────────────│
                                   │ PK id              │
                                   │ FK subscription_id │
                                   │ cycle_number        │
                                   │ amount              │
                                   │ status (pending/    │
                                   │   completed/failed) │
                                   │ charged_at          │
                                   └────────────────────┘
```
