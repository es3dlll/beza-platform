# 02 - مكان العملية

```
  Merchant (Flutter/React)
       │ POST /settlement
       ▼
  ┌────────────────────┐
  │ SettlementController │
  └──────────┬─────────┘
             │
  ┌──────────┴─────────┐
  │ SettlementService     │
  │ 1. Calculate amount   │
  │ 2. Apply fees (1%)    │
  │ 3. Check min (50 USD) │
  │ 4. Create settlement  │
  │ 5. Bank transfer      │
  │ 6. Update wallet      │
  └──────────┬─────────┘
             │
        ┌────┴────┐
        │  MySQL  │
        │settle-  │
        │  ments  │
        │wallets  │
        └─────────┘
```
