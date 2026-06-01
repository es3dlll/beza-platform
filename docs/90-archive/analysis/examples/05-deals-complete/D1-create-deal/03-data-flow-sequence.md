# 03 - تدفق البيانات الكامل (Sequence Diagram)

## سلسلة الاستدعاءات الكاملة خطوة بخطوة

```
                         ┌──────────────────────────────────────────────────────────────────────────┐
                         │            SEQUENCE DIAGRAM — Create Deal (Admin)                        │
                         └──────────────────────────────────────────────────────────────────────────┘

  Admin             React Admin           Laravel API         AdminDealService        MySQL          Queue
   │                    │                     │                      │                 │            │
   │  Click "إنشاء"    │                     │                      │                 │            │
   │───────────────────>│                     │                      │                 │            │
   │                    │                     │                      │                 │            │
   │  Fill form:        │                     │                      │                 │            │
   │  title,amount,cat  │                     │                      │                 │            │
   │───────────────────>│                     │                      │                 │            │
   │                    │                     │                      │                 │            │
   │                    │  POST /admin/deals  │                      │                 │            │
   │                    │  {title,desc,...}   │                      │                 │            │
   │                    │────────────────────>│                      │                 │            │
   │                    │                     │                      │                 │            │
   │                    │                     │  ─── Middleware ───  │                 │            │
   │                    │                     │  1. auth:api     │                 │            │
   │                    │                     │  2. is_admin         │                 │            │
   │                    │                     │                      │                 │            │
   │                    │                     │  validate(request)   │                 │            │
   │                    │                     │                      │                 │            │
   │                    │                     │  AdminDealService    │                 │            │
   │                    │                     │  ::create(data)      │                 │            │
   │                    │                     │─────────────────────>│                 │            │
   │                    │                     │                      │                 │            │
   │                    │                     │  Set default status  │                 │            │
   │                    │                     │  = 'pending'         │                 │            │
   │                    │                     │                      │                 │            │
   │                    │                     │  BEGIN TRANSACTION   │                 │            │
   │                    │                     │                      │                 │            │
   │                    │                     │  INSERT INTO deals   │                 │            │
   │                    │                     │  (title,desc,amount, │                 │            │
   │                    │                     │   profit%,duration,  │                 │            │
   │                    │                     │   category,risk,     │                 │            │
   │                    │                     │   status:pending)    │                 │            │
   │                    │                     │──────────────────────>────────────────>            │
   │                    │                     │                      │                 │            │
   │                    │                     │  COMMIT              │                 │            │
   │                    │                     │                      │                 │            │
   │                    │                     │  dispatch event      │                 │            │
   │                    │                     │  DealCreated         │                 │            │
   │                    │                     │─────────────────────>│                 │            │
   │                    │                     │                      │                 │            │
   │                    │  Response 201       │                      │                 │            │
   │                    │<────────────────────│                      │                 │            │
   │  Show success      │                     │                      │                 │            │
   │<───────────────────│                     │                      │                 │            │
   │                    │                     │                      │                 │            │
   │                    │                     │  ─── Async ───       │                 │            │
   │                    │                     │  Listener:           │                 │            │
   │                    │                     │  SendDealCreatedN.   │                 │            │
   │                    │                     │──────────────────────────────────────────────────>│
```

## جدول زمني لتنفيذ العملية

| الخطوة | الزمن المستغرق |
|--------|---------------|
| Network Request | ~30ms |
| Middleware Stack | ~10ms |
| Validation | ~5ms |
| DB Transaction (INSERT) | ~15ms |
| Event Dispatch | ~2ms |
| Response | ~10ms |
| **المجموع** | **~72ms** |
