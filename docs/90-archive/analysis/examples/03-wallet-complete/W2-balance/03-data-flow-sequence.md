# 03 - تدفق البيانات الكامل (Sequence Diagram)

## سلسلة الاستدعاءات الكاملة خطوة بخطوة

```
                        ┌──────────────────────────────────────────────────────────────────────┐
                        │               SEQUENCE DIAGRAM — GET Balance                         │
                        └──────────────────────────────────────────────────────────────────────┘

  User              Flutter/React          Laravel API          BalanceService      MySQL         Redis
   │                     │                     │                      │               │            │
   │  Open home screen   │                     │                      │               │            │
   │────────────────────>│                     │                      │               │            │
   │                     │                     │                      │               │            │
   │                     │  GET /api/balance   │                      │               │            │
   │                     │  (Bearer Token)     │                      │               │            │
   │                     │────────────────────>│                      │               │            │
   │                     │                     │                      │               │            │
   │                     │                     │  ─── Middleware ───  │               │            │
   │                     │                     │  auth:api        │               │            │
   │                     │                     │  throttle:60,1       │               │            │
   │                     │                     │                      │               │            │
   │                     │                     │  BalanceService      │               │            │
   │                     │                     │  ::getBalance()      │               │            │
   │                     │                     │─────────────────────>│               │            │
   │                     │                     │                      │               │            │
   │                     │                     │  Check cache:        │               │            │
   │                     │                     │  "balance:user:{id}" │               │            │
   │                     │                     │────────────────────────────────────────────────>│
   │                     │                     │                      │               │            │
   │                     │                     │  Cache MISS (أو HIT) │               │            │
   │                     │                     │<────────────────────────────────────────────────│
   │                     │                     │                      │               │            │
   │                     │                     │  If MISS:            │               │            │
   │                     │                     │  Query wallets       │               │            │
   │                     │                     │  WHERE user_id = ?   │               │            │
   │                     │                     │─────────────────────────────────────>│            │
   │                     │                     │                      │               │            │
   │                     │                     │  Return wallets      │               │            │
   │                     │                     │<─────────────────────────────────────│            │
   │                     │                     │                      │               │            │
   │                     │                     │  Store in Redis:     │               │            │
   │                     │                     │  SETEX 30 sec        │               │            │
   │                     │                     │────────────────────────────────────────────────>│
   │                     │                     │                      │               │            │
   │                     │                     │  Return formatted    │               │            │
   │                     │                     │<─────────────────────│               │            │
   │                     │                     │                      │               │            │
   │                     │  Response 200       │                      │               │            │
   │                     │<────────────────────│                      │               │            │
   │                     │                     │                      │               │            │
   │  Show balance       │                     │                      │               │            │
   │<────────────────────│                     │                      │               │            │
```

## جدول زمني لتنفيذ العملية (استجابة < 50ms p95)

| الخطوة | الزمن المستغرق | ملاحظة |
|--------|---------------|--------|
| Network Request (Flutter → API) | ~30ms | يعتمد على سرعة الشبكة |
| Middleware Stack | ~5ms | Auth + Rate Limit |
| Redis Cache Check | ~1ms | Key lookup |
| DB Query (إذا Cache MISS) | ~5ms | Query بسيط مع Index |
| Response Serialization | ~3ms | JSON |
| Network Response (API → Flutter) | ~30ms | |
| **المجموع (Cache HIT)** | **~69ms** | ضمن الهدف < 50ms (بدون شبكة) |
| **المجموع (Cache MISS)** | **~74ms** | لا يزال سريعاً |
