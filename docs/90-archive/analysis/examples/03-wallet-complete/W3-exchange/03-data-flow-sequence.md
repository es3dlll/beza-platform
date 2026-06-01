# 03 - تدفق البيانات الكامل (Sequence Diagram)

## سلسلة الاستدعاءات الكاملة خطوة بخطوة

```
                        ┌─────────────────────────────────────────────────────────────────────────────────────┐
                        │               SEQUENCE DIAGRAM — Currency Exchange (SYP ↔ USD)                      │
                        └─────────────────────────────────────────────────────────────────────────────────────┘

  User              Flutter/React          Laravel API            ExchangeService      WalletService    MySQL          Redis        FCM
   │                     │                     │                      │                 │               │            │            │
   │  Click "صرافة"     │                     │                      │                 │               │            │            │
   │────────────────────>│                     │                      │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │  Fill form:         │                     │                      │                 │               │            │            │
   │  from=SYP,to=USD    │                     │                      │                 │               │            │            │
   │  amount=100000      │                     │                      │                 │               │            │            │
   │────────────────────>│                     │                      │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │  POST /api/exchange │                      │                 │               │            │            │
   │                     │  {from,to,amount}   │                      │                 │               │            │            │
   │                     │────────────────────>│                      │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  ─── Middleware ───  │                 │               │            │            │
   │                     │                     │  1. auth:api     │                 │               │            │            │
   │                     │                     │  2. throttle:20,1    │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  validate($request)  │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  ExchangeService     │                 │               │            │            │
   │                     │                     │  ::exchange()        │                 │               │            │            │
   │                     │                     │─────────────────────>│                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  Validate currencies │                 │               │            │            │
   │                     │                     │  Check min amount    │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  Get exchange rate   │                 │               │            │            │
   │                     │                     │  from config/market  │───────────────────────────────────────────>            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  Calculate fee (1.5%)│                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  Get fromWallet      │                 │               │            │            │
   │                     │                     │─────────────────────>│─────────────────>               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  Check balance       │                 │               │            │            │
   │                     │                     │  >= amount + fee     │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  BEGIN TRANSACTION   │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  Decrement from      │                 │               │            │            │
   │                     │                     │  (amount + fee)      │                 │               │            │            │
   │                     │                     │─────────────────────>│─────────────────>───────────────────────────────────>            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  Increment to        │                 │               │            │            │
   │                     │                     │  (converted amount)  │                 │               │            │            │
   │                     │                     │─────────────────────>│─────────────────>───────────────────────────────────>            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  INSERT transaction  │                 │               │            │            │
   │                     │                     │  (type:exchange)     │                 │               │            │            │
   │                     │                     │─────────────────────>│─────────────────>───────────────────────────────────>            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  COMMIT              │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  Cache::forget       │                 │               │            │            │
   │                     │                     │  (balance cache)     │────────────────────────────────────────────>            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │                     │  dispatch Event      │                 │               │            │            │
   │                     │                     │  ExchangeCompleted   │                 │               │            │            │
   │                     │                     │─────────────────────>│                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │                     │  Response 200       │                      │                 │               │            │            │
   │                     │<────────────────────│                      │                 │               │            │            │
   │                     │                     │                      │                 │               │            │            │
   │  Show success       │                     │                      │                 │               │            │            │
   │<────────────────────│                     │                      │                 │               │            │            │
   │                     │                     │  ─── Async ───       │                 │               │            │            │
   │                     │                     │  SendExchangeNotif   │                 │               │            │            │
   │                     │                     │──────────────────────────────────────────────────────────────────────────────────>│
```

## جدول زمني لتنفيذ العملية (استجابة < 300ms p95)

| الخطوة | الزمن المستغرق | ملاحظة |
|--------|---------------|--------|
| Network Request (Flutter → API) | ~30ms | |
| Middleware Stack | ~10ms | Auth + Rate Limit |
| Validation | ~5ms | |
| جلب سعر الصرف | ~2ms | من Config/Cache |
| حساب الرسوم | ~1ms | |
| DB Transaction (خصم + إضافة + تسجيل) | ~25ms | Atomic |
| Cache clear | ~1ms | |
| Event Dispatch | ~2ms | Queued |
| Response Serialization | ~10ms | |
| Network Response (API → Flutter) | ~30ms | |
| **المجموع** | **~116ms** | ضمن الهدف < 300ms |
