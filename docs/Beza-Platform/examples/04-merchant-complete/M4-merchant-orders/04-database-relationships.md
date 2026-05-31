# 04 - علاقات الجداول

```
┌──────────────────┐        ┌─────────────────────────────────────┐
│    merchants      │───────>│          merchant_orders             │
│──────────────────│ 1     M│─────────────────────────────────────│
│ id               │        │ PK id                               │
└──────────────────┘        │ FK merchant_id                      │
                            │ FK customer_id → users.id           │
                            │ status (pending/processing/         │
                            │   shipped/delivered/cancelled)       │
                            │ total_amount                        │
                            │ currency                            │
                            │ notes                               │
                            │ created_at / updated_at              │
                            └────────────────┬────────────────────┘
                                             │ 1
                                             │ hasMany
                                             ▼
                                   ┌────────────────────┐
                                   │     order_items     │
                                   │────────────────────│
                                   │ PK id              │
                                   │ FK order_id        │
                                   │ product_name        │
                                   │ quantity            │
                                   │ unit_price          │
                                   └────────────────────┘
```
