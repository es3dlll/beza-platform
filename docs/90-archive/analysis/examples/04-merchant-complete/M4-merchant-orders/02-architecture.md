# 02 - مكان العملية

```
  Merchant (Flutter/React)
       │ GET /orders | PATCH /orders/{id}/status
       ▼
  ┌────────────────────┐
  │ MerchantOrderController │
  └──────────┬─────────┘
             │
  ┌──────────┴─────────┐
  │ OrderService         │
  │ 1. List orders       │
  │ 2. Update status     │
  │ 3. Notify customer   │
  └──────────┬─────────┘
             │
        ┌────┴────┐
        │  MySQL  │
        │  orders │
        │  items  │
        └─────────┘
```
