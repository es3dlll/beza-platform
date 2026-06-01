# 03 - تدفق البيانات الكامل (Sequence Diagram)

## سلسلة الاستدعاءات الكاملة خطوة بخطوة

```
                        ┌─────────────────────────────────────────────────────────────────────────────────────┐
                        │                   SEQUENCE DIAGRAM — P2P Transfer                                 │
                        └─────────────────────────────────────────────────────────────────────────────────────┘

  User              Flutter/React          Laravel API            TransferService    WalletService       MySQL         Redis         FCM
   │                     │                     │                      │                 │                 │            │            │
   │   Click "تحويل"    │                     │                      │                 │                 │            │            │
   │────────────────────>│                     │                      │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │  Fill form:         │                     │                      │                 │                 │            │            │
   │  phone,amount,pin   │                     │                      │                 │                 │            │            │
   │────────────────────>│                     │                      │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │  POST /api/transfer │                      │                 │                 │            │            │
   │                     │  {to_phone,amount,  │                      │                 │                 │            │            │
   │                     │   currency,pin}     │                      │                 │                 │            │            │
   │                     │────────────────────>│                      │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │  ─── Middleware ───  │                 │                 │            │            │
   │                     │                     │  1. auth:api     │                 │                 │            │            │
   │                     │                     │  2. throttle:30,1    │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │  validate($request)  │                 │                 │            │            │
   │                     │                     │  ────────────────    │                 │                 │            │            │
   │                     │                     │  to_phone: exists    │                 │                 │            │            │
   │                     │                     │  amount: numeric,min │                 │                 │            │            │
   │                     │                     │  currency: in:SYP,USD│                 │                 │            │            │
   │                     │                     │  pin: size:4         │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │  TransferService     │                 │                 │            │            │
   │                     │                     │  ::transfer()        │                 │                 │            │            │
   │                     │                     │─────────────────────>│                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Find $fromUser     │                 │                 │            │            │
   │                     │                     │   (from auth token)  │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Find $toUser       │                 │                 │            │            │
   │                     │                     │   WHERE phone=...    │────────────────────────────────────────────>            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Check self-transfer│                 │                 │            │            │
   │                     │                     │   $fromUser->id ==   │                 │                 │            │            │
   │                     │                     │   $toUser->id ?→ 400 │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Verify PIN          │                 │                 │            │            │
   │                     │                     │   Hash::check(pin,   │                 │                 │            │            │
   │                     │                     │     fromUser.pin)    │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Get wallets         │                 │                 │            │            │
   │                     │                     │─────────────────────>│─────────────────>                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Check balance       │                 │                 │            │            │
   │                     │                     │   fromWallet.balance │                 │                 │            │            │
   │                     │                     │   >= amount ?→ 400   │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Check daily limit   │                 │                 │            │            │
   │                     │                     │   sum(today)+amount   │                 │                 │            │            │
   │                     │                     │   <= limit ?→ 400    │────────────────────────────────────────────>            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   BEGIN TRANSACTION  │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Decrement sender    │                 │                 │            │            │
   │                     │                     │   UPDATE wallets SET │                 │                 │            │            │
   │                     │                     │   balance = balance  │                 │                 │            │            │
   │                     │                     │   - amount           │                 │                 │            │            │
   │                     │                     │─────────────────────>│─────────────────>───────────────────────────────────>            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   Increment receiver │                 │                 │            │            │
   │                     │                     │   UPDATE wallets SET │                 │                 │            │            │
   │                     │                     │   balance = balance  │                 │                 │            │            │
   │                     │                     │   + amount           │                 │                 │            │            │
   │                     │                     │─────────────────────>│─────────────────>───────────────────────────────────>            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   INSERT transaction │                 │                 │            │            │
   │                     │                     │   (type:transfer,    │                 │                 │            │            │
   │                     │                     │    status:completed) │                 │                 │            │            │
   │                     │                     │─────────────────────>│─────────────────>───────────────────────────────────>            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   COMMIT             │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │   dispatch Event     │                 │                 │            │            │
   │                     │                     │   TransactionCompleted│                 │                 │            │            │
   │                     │                     │─────────────────────>│                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │  Return success      │                 │                 │            │            │
   │                     │                     │<─────────────────────│                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │  Response 200       │                      │                 │                 │            │            │
   │                     │<────────────────────│                      │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │  ─── Async ───       │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │                     │                     │  Listener:           │                 │                 │            │            │
   │                     │                     │  SendTransactionNotif│                 │                 │            │            │
   │                     │                     │──────────────────────────────────────────────────────────────────────────────────>│
   │                     │                     │                      │                 │                 │            │  Send     │
   │                     │                     │                      │                 │                 │            │  Push     │
   │                     │                     │                      │                 │                 │            │  Notif.   │
   │                     │                     │                      │                 │                 │            │  to both  │
   │                     │                     │                      │                 │                 │            │  users    │
   │                     │                     │                      │                 │                 │            │<──────────│
   │                     │                     │                      │                 │                 │            │            │
   │  Show success       │                     │                      │                 │                 │            │            │
   │<────────────────────│                     │                      │                 │                 │            │            │
   │                     │                     │                      │                 │                 │            │            │
   │  Receive push       │                     │                      │                 │                 │            │            │
   │<───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────│
```

## جدول زمني لتنفيذ العملية (استجابة < 200ms p95)

| الخطوة | الزمن المستغرق | ملاحظة |
|--------|---------------|--------|
| Network Request (Flutter → API) | ~30ms | يعتمد على سرعة الشبكة |
| Middleware Stack | ~10ms | Auth + Rate Limit |
| Validation | ~5ms | تحقق من الحقول |
| البحث عن المستخدمين | ~10ms | Query MySQL مع Index |
| التحقق من PIN | ~3ms | Hash::check |
| DB Transaction (خصم + إضافة + تسجيل) | ~20ms | Atomic |
| Event Dispatch | ~2ms | Queued |
| Response Serialization | ~10ms | JSON |
| Network Response (API → Flutter) | ~30ms | |
| **المجموع** | **~120ms** | ضمن الهدف < 200ms |
