# 03 - تدفق البيانات (Sequence Diagram)

```
  User            Flutter/Web          Laravel API         DisputeService       MySQL          FCM
   │                  │                     │                    │               │             │
   │  تقديم نزاع      │                     │                    │               │             │
   │─────────────────>│                     │                    │               │             │
   │                  │  POST /support/     │                    │               │             │
   │                  │  disputes           │                    │               │             │
   │                  │───────────────────>│                    │               │             │
   │                  │                     │  إنشاء نزاع        │               │             │
   │                  │                     │  + رفع أدلة        │──────────────>│             │
   │                  │                     │  status=open       │               │             │
   │                  │<───────────────────│                    │               │             │
   │<─────────────────│                     │                    │               │             │
   │                  │                     │                    │               │             │
  Admin               React Admin           Laravel API         DisputeService   MySQL         FCM
   │                  │                     │                    │               │             │
   │  فتح النزاعات    │                     │                    │               │             │
   │─────────────────>│                     │                    │               │             │
   │                  │  GET /admin/disputes│                    │               │             │
   │                  │───────────────────>│  SELECT disputes    │               │             │
   │                  │                     │  WHERE status=open │──────────────>│             │
   │                  │<───────────────────│                    │               │             │
   │<─────────────────│                     │                    │               │             │
   │                  │                     │                    │               │             │
   │  اتخاذ قرار      │                     │                    │               │             │
   │─────────────────>│                     │                    │               │             │
   │                  │  POST /admin/       │                    │               │             │
   │                  │  disputes/1/resolve │                    │               │             │
   │                  │───────────────────>│                    │               │             │
   │                  │                     │  DB::transaction   │               │             │
   │                  │                     │──────────────────>│               │             │
   │                  │                     │  UPDATE disputes   │               │             │
   │                  │                     │  SET status=       │               │             │
   │                  │                     │  resolved          │──────────────>│             │
   │                  │                     │  إذا refund:       │               │             │
   │                  │                     │  UPDATE wallets    │               │             │
   │                  │                     │  SET balance + ... │──────────────>│             │
   │                  │                     │  UPDATE trans.     │               │             │
   │                  │                     │  SET status=       │               │             │
   │                  │                     │  refunded          │──────────────>│             │
   │                  │                     │  COMMIT            │               │             │
   │                  │                     │                    │               │             │
   │                  │                     │  dispatch Event    │               │             │
   │                  │                     │  DisputeResolved   │               │  إشعار    │
   │                  │                     │                    │               │──────────>│
   │                  │<───────────────────│                    │               │             │
   │<─────────────────│                     │                    │               │             │
```

## جدول زمني

| الخطوة | الزمن |
|--------|-------|
| تقديم نزاع + رفع أدلة | ~200ms |
| عرض النزاعات المفتوحة | ~50ms |
| حل النزاع (refund) | ~100ms |
