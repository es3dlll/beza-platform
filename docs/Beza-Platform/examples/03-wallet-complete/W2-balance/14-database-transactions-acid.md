# 14 - ACID + الأقفال + الـ Race Conditions

## عرض الرصيد هو عملية READ — لا تحتاج ACID

عرض الرصيد لا يغيّر البيانات، لذلك:
- **لا حاجة** لـ DB::transaction
- **لا حاجة** لـ FOR UPDATE
- **لا حاجة** لأقفال Pessimistic

## لكن Cache يضيف تعقيداً

```
Time  |  طلب الرصيد 1               |  معاملة (تحويل/صرافة)     |  طلب الرصيد 2
------|-----------------------------|---------------------------|-----------------------------
T1    |  Cache MISS → Query DB     |                           |
T2    |  Cache: store {balance:500} |                           |
T3    |                               |  decrement: balance=400  |
T4    |                               |  Cache::forget()         |
T5    |  Cache موجود → return 500   |                           | ← خطأ! الرصيد 400
```

**المشكلة**: Cache قديم لمدة قصيرة. **الحل**: TTL = 30 ثانية فقط. الفارق الزمني ضئيل.

## Consistent Reading (القراءة المتسقة)

InnoDB يستخدم **MVCC** (Multiversion Concurrency Control):
- القراءة العادية (`SELECT`) ترى committed data فقط
- لا تحتاج أقفال للقراءة
- القراءة لا تمنع الكتابة (Non-blocking)

```sql
-- SELECT بسيط — لا قفل
SELECT balance, frozen_balance
FROM wallets
WHERE user_id = 1 AND currency = 'SYP';

-- InnoDB يقرأ أحدث committed snapshot
```

## Cache Invalidation Strategy

استراتيجية إبطال Cache المستخدمة:

```
┌─────────────────────────────────────────────┐
│          Cache Strategy (Write-Through)      │
│                                             │
│  READ:                                      │
│  ┌──────────┐    Cache HIT?                 │
│  │ Request  │───→ YES → Return cached       │
│  │ Balance  │    │                          │
│  └──────────┘    NO                         │
│                  │                          │
│                  ▼                          │
│           ┌──────────────┐                  │
│           │ Query MySQL   │                  │
│           │ Store→Redis   │                  │
│           │ Return data   │                  │
│           └──────────────┘                  │
│                                             │
│  WRITE (decrement/increment):               │
│  ┌──────────┐    ┌──────────┐               │
│  │ Update DB │───→│ Cache    │               │
│  │           │    │ ::forget │               │
│  └──────────┘    └──────────┘               │
│                                             │
│  → المرة القادمة: Cache MISS → DB Query     │
└─────────────────────────────────────────────┘
```

## التعامل مع فشل Cache

إذا فشل Redis (Cache Server Down):

```php
public function getBalance(User $user): array
{
    try {
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
    } catch (\Throwable $e) {
        // Redis فشل → تجاهل Cache واذهب إلى DB
        Log::warning('Redis unavailable, falling back to DB', [
            'user_id' => $user->id,
        ]);
    }

    // Fallback: القراءة من DB مباشرة
    $wallets = $this->walletService->getUserWallets($user->id);

    return $wallets;
}
```

## ملخص

| الميزة | القيمة |
|--------|--------|
| نوع العملية | READ فقط |
| ACID مطلوب؟ | لا |
| FOR UPDATE؟ | لا |
| Cache | Redis (TTL 30 ثانية) |
| إبطال Cache | عند كل تعديل للرصيد |
| Fallback | إذا فشل Redis → DB مباشرة |
