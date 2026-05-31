# 02 - البنية المعمارية (Architecture) - بوابة الدفع (Payment Gateway)

```
  Merchant (Flutter/React)
       │ POST /payment-link
       ▼
  ┌────────────────────┐
  │ PaymentLinkController │
  └──────────┬─────────┘
             │
  ┌──────────┴─────────┐
  │ PaymentLinkService   │
  │ 1. Create link       │
  │ 2. Freeze amount     │
  │ 3. Generate URL      │
  └──────────┬─────────┘
             │
  Customer (Browser)
       │ Click link
       ▼
  ┌────────────────────┐
  │ Payment Page        │
  │ (Flutter Web/React) │
  └──────────┬─────────┘
             │ POST /pay
             ▼
  ┌────────────────────┐
  │ PaymentController    │
  │ 1. Process payment   │
  │ 2. Unfreeze + deduct │
  │ 3. Send webhook      │
  └────────────────────┘
```
