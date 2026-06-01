# 14 - المعاملات الذرية ACID (Database Transactions & Deadlock Prevention)

## تحليل ACID لعملية الشراء

### Atomicity (الذرية)

```
DB::transaction(function () {
    1. lockForUpdate(wallet)       // قفل المحفظة
    2. lockForUpdate(holding)      // قفل الحيازة
    3. decrement(wallet, 500)      // خصم من المحفظة
    4. upsert(holding)             // إضافة الجرامات
    5. create(transaction)         // تسجيل المعاملة
});

كل العمليات تنجح معاً أو تفشل معاً (All or Nothing)
إذا فشلت أي خطوة → ROLLBACK كامل
```

### Consistency (الاتساق)

```
قبل العملية:
  - wallet.balance = 1000 USD
  - holding.grams = 0.0000

بعد العملية:
  - wallet.balance = 500 USD (1000 - 500)
  - holding.grams = 4.8544 (500 / 103.0)
  - transaction موجودة بسجل جديد

القيود (Constraints):
  - UNIQUE(user_id, commodity) → يضمن عدم وجود حيازتين لنفس المستخدم
  - reference_number UNIQUE → يمنع ازدواجية المعاملات
  - CHECK (grams >= 0) → يمنع رصيد سالب
```

### Isolation (العزل — SERIALIZABLE)

```
المشكلة: سيناريو السباق (Race Condition)
  - المستخدم يرسل طلبين Buy متزامنين
  - بدون قفل: قد يُستخدم نفس الرصيد مرتين

الحل:
  1. lockForUpdate() ← قفل Pessimistic
  2. يمنع القراءات الأخرى من تعديل نفس الصف
  3. الطلب الثاني ينتظر حتى ينتهي الأول

الترتيب الثابت للأقفال (Fixed Order Locking):
  قفل بترتيب ثابت: 1) wallet → 2) holding
  يمنع Deadlock بين عمليات Buy و Sell المتزامنة
```

### Durability (الديمومة)

```
- InnoDB يسجل في Redo Log قبل الـ COMMIT
- بعد COMMIT → البيانات مكتوبة على القرص
- حتى لو انقطعت الكهرباء → After Crash Recovery يستعيد البيانات
```

## كود ACID الكامل لعملية Buy

```php
<?php
// من CommodityService::executeBuy

$result = DB::transaction(function () use (
    $user, $commodity, $grams, $amountInUsd, $fee,
    $netAmount, $pricePerGram, $currency, $wallet
) {
    // ─── الخطوة 1: قفل المحفظة (Pessimistic Lock) ───
    // SELECT ... FOR UPDATE يمنع أي تعديل آخر على هذا الصف
    $lockedWallet = $this->walletService->lockForUpdate($wallet->id);

    // التحقق من الرصيد بعد القفل
    if ($lockedWallet->balance < $amountInUsd) {
        throw new InsufficientBalanceException(
            available: (float) $lockedWallet->balance,
            required:  $amountInUsd,
        );
    }

    // ─── الخطوة 2: قفل الحيازة (أو إنشاء قفل على صف جديد) ───
    $holding = CommodityHolding::where('user_id', $user->id)
        ->where('commodity', $commodity)
        ->lockForUpdate()
        ->first();

    // ─── الخطوة 3: خصم من المحفظة ───
    $lockedWallet->decrement('balance', $amountInUsd);

    // ─── الخطوة 4: إنشاء أو تحديث الحيازة ───
    if ($holding) {
        $totalGrams    = $holding->grams + $grams;
        $totalInvested = $holding->total_invested_usd + $amountInUsd;
        $avgPrice      = round($totalInvested / $totalGrams, 2);

        $holding->update([
            'grams'              => $totalGrams,
            'avg_price_usd'      => $avgPrice,
            'total_invested_usd' => $totalInvested,
        ]);
    } else {
        $holding = CommodityHolding::create([
            'user_id'            => $user->id,
            'commodity'          => $commodity,
            'grams'              => $grams,
            'avg_price_usd'      => $pricePerGram,
            'total_invested_usd' => $amountInUsd,
        ]);
    }

    // ─── الخطوة 5: تسجيل المعاملة ───
    $txn = CommodityTransaction::create([
        'user_id'         => $user->id,
        'commodity'       => $commodity,
        'type'            => 'buy',
        'grams'           => $grams,
        'price_usd'       => $pricePerGram,
        'total_usd'       => $amountInUsd,
        'fee'             => $fee,
        'reference_number'=> CommodityTransaction::generateReferenceNumber(),
        'status'          => 'completed',
    ]);

    return ['holding' => $holding, 'txn' => $txn];

}, attempts: 3); // إعادة محاولة تلقائية 3 مرات في حال Deadlock
```

## منع Deadlock (Deadlock Prevention)

### المشكلة
```
المعاملة 1 (Buy):    lock(wallet) → lock(holding) → ... COMMIT
المعاملة 2 (Sell):   lock(wallet) → lock(holding) → ... COMMIT

إذا كان الترتيب مختلفاً:
  T1: lock(wallet_A) → lock(holding_A)
  T2: lock(holding_A) → lock(wallet_A)
  → DEADLOCK! كل معاملة تنتظر الأخرى
```

### الحل: الترتيب الثابت للأقفال (Fixed-Order Locking)

```php
<?php
// قاعدة صارمة: الترتيب دائماً wallet → holding

// ⚠️ ممنوع: ترتيب مختلف للمعاملة
// DB::transaction(function () {
//     lockForUpdate(holding)  ← خطأ! يجب أن يكون wallet أولاً
//     lockForUpdate(wallet)
// });

// ✅ صحيح: دائماً wallet → holding
DB::transaction(function () {
    $this->walletService->lockForUpdate($wallet->id);      // 1. wallet أولاً
    CommodityHolding::where(...)->lockForUpdate()->first(); // 2. holding ثانياً
    // ... بقية المنطق
});
```

### آلية إعادة المحاولة (Retry on Deadlock)

```php
<?php
// Laravel يدعم إعادة المحاولة تلقائياً
DB::transaction($callback, attempts: 3);

// عند حدوث Deadlock:
// 1. MySQL يلقي خطأ: "Deadlock found when trying to get lock"
// 2. Laravel يلتقط الاستثناء ويعيد المحاولة حتى 3 مرات
// 3. بين كل محاولة: تأخير عشوائي قصير (random backoff)
```

## تحليل أداء القفل

| السيناريو | مدة القفل | التأثير |
|-----------|----------|---------|
| Buy واحد | ~50ms | القفل محصور على صف المحفظة والحيازة فقط |
| Buy متزامن (نفس المستخدم) | الأول ~50ms، الثاني ينتظر ~50ms | آمن، بدون ازدواجية |
| Buy + Sell متزامنان (نفس المستخدم) | ينتظران حسب الترتيب | آمن، بدون Deadlock (نفس ترتيب القفل) |
| Buy لمستخدمين مختلفين | متوازيان (أقفال على صفوف مختلفة) | بدون تعارض |

## اختبارات ACID

```php
<?php
/** @test */
public function test_concurrent_buy_prevents_double_spending()
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create([
        'user_id' => $user->id,
        'currency' => 'USD',
        'balance' => 1000,
    ]);

    // محاكاة طلبين متزامنين
    $this->expectException(InsufficientBalanceException::class);

    // طلب 1: يستهلك 600
    // طلب 2: يستهلك 600 (غير كافٍ بعد الطلب الأول)
    DB::transaction(function () use ($wallet) {
        $locked = Wallet::lockForUpdate()->find($wallet->id);
        $locked->decrement('balance', 600);
    });

    DB::transaction(function () use ($wallet) {
        $locked = Wallet::lockForUpdate()->find($wallet->id);
        // يجب أن يتبقى 400 فقط
        if ($locked->balance < 600) {
            throw new InsufficientBalanceException($locked->balance, 600);
        }
    });
}
```
