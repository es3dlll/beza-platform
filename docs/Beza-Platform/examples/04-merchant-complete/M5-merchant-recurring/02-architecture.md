# 02 - مكان العملية

```
  Merchant (Flutter/React)          Customer
       │ POST /subscriptions            │
       ▼                                │
  ┌──────────────────┐                  │
  │ SubscriptionController │             │
  └──────────┬───────┘                  │
             │                          │
  ┌──────────┴───────┐                  │
  │ SubscriptionService │               │
  │ 1. Create subscription│             │
  │ 2. Confirm consent    │             │
  └──────────┬───────┘                  │
             │                          │
  ┌──────────┴───────┐                  │
  │ RecurringBilling   │                │
  │ Service (Cron)     │                │
  │ 1. Check due subs  │               │
  │ 2. Process charge  │               │
  │ 3. Notify 3 days before│           │
  └──────────┬───────┘                  │
             │                          │
        ┌────┴────┐                     │
        │  MySQL  │                     │
        │ subs    │  ← Consent ←───────│
        │ charges │                     │
        └─────────┘
```
