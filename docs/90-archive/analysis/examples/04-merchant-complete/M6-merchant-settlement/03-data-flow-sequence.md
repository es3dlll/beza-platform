# 03 - تدفق البيانات

```
  Merchant         API           SettlementService     BankAPI         MySQL        Admin
     │               │                   │                │              │            │
     │  طلب تسوية    │                   │                │              │            │
     │──────────────>│                   │                │              │            │
     │               │  POST /settlement │                │              │            │
     │               │──────────────────>│                │              │            │
     │               │  Calculate sales  │─────────────────────────────>│            │
     │               │  - fees (2%)      │                │              │            │
     │               │  - refunds        │                │              │            │
     │               │  - transfer fee   │                │              │            │
     │               │                   │                │              │            │
     │               │  Check min (50)   │                │              │            │
     │               │  Create settlement│─────────────────────────────>│            │
     │               │  (status:pending) │                │              │            │
     │               │                   │                │              │            │
     │               │  Response         │                │              │            │
     │<──────────────│                   │                │              │            │
     │               │                   │                │              │            │
     │               │                   │  ── Admin ──  │              │            │
     │               │                   │                │              │            │
     │               │                   │  Transfer API  │              │            │
     │               │                   │────────────────>              │            │
     │               │                   │<───────────────│              │            │
     │               │                   │                │              │            │
     │               │  Update settled   │─────────────────────────────>│            │
     │               │  Notify merchant  │                │              │            │
     │<──────────────│                   │                │              │            │
```
