# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. Same-Currency Attack

```php
// ❌ خطأ: السماح بنفس العملة
$convertedAmount = $amount; // SYP → SYP — يضاعف الرصيد!

// ✅ صحيح: منع الصرافة لنفس العملة
if ($fromCurrency === $toCurrency) {
    throw new SameCurrencyExchangeException();
}
```

## 2. SQL Injection

```php
// ❌ خطأ: استخدام interpolation
DB::statement("UPDATE wallets SET balance = balance + {$converted} WHERE id = {$walletId}");

// ✅ صحيح: Parameter binding
DB::update('UPDATE wallets SET balance = balance + ? WHERE id = ?', [$converted, $walletId]);
```

## 3. Rate Manipulation

```php
// ❌ خطأ: السماح للمستخدم بتحديد سعر الصرف
$rate = $request->input('rate');

// ✅ صحيح: استخدام السعر من Config/DB
$rate = $this->rateService->getRate($fromCurrency, $toCurrency);
```

## 4. IDOR

```php
// ❌ خطأ: المستخدم يحدد user_id
$user = User::find($request->input('user_id'));

// ✅ صحيح: المستخدم هو المستخدم المصادق
$user = $request->user();
$fromWallet = $this->walletService->getWallet($user->id, $fromCurrency); // محفظة المستخدم فقط
```

## 5. Mass Assignment

```php
// ❌ خطأ: السماح بكل الحقول
Transaction::create($request->all());

// ✅ صحيح: تحديد الحقول المسموحة
Transaction::createExchange(
    fromWallet: $fromWallet,
    toWallet:   $toWallet,
    amount:     $amount,
    amountInUsd: $amountInUsd,
    fee:        $feeAmount,
    metadata:   [...], // مقيدة
);
```

## 6. Rate Limiting

```php
// routes/api.php
Route::middleware(['auth:api', 'throttle:20,1'])->group(function () {
    Route::post('/wallet/exchange', [ExchangeController::class, 'exchange']);
});
```

| الإعداد | القيمة | السبب |
|---------|--------|-------|
| max_attempts | 20 | كافٍ للصرافة — أقل من التحويل لأنها أقل استخداماً |
| decay_minutes | 1 | 20 محاولة في الدقيقة |

## 7. Race Condition (TOCTOU)

```php
// ❌ خطأ: Time-of-check to Time-of-use
$balance = $fromWallet->balance;
if ($balance >= $amount + $fee) {
    $fromWallet->decrement('balance', $amount + $fee); // قد يكون الرصيد تغير!
}

// ✅ صحيح: WHERE balance >= amount + fee في نفس الاستعلام
DB::update(
    'UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ? AND is_active = ?',
    [$totalDeduction, $fromWallet->id, $totalDeduction, true]
);
// إذا 0 rows → رفض
```

## 8. Fee Manipulation

```php
// ❌ خطأ: الرسوم من المستخدم
$feePercentage = $request->input('fee_percentage', 1.5);

// ✅ صحيح: الرسوم من Config
$feePercentage = config('beza.exchange.fee_percentage', 1.5);
```

## 9. Audit Logging

```php
Log::info('Exchange completed', [
    'user_id'       => $user->id,
    'from'          => $fromCurrency,
    'to'            => $toCurrency,
    'amount'        => $amount,
    'converted'     => $convertedAmount,
    'fee'           => $feeAmount,
    'rate'          => $rate,
    'reference'     => $transaction->reference_number,
    'ip'            => request()->ip(),
]);
```

## 10. قائمة التحقق الأمني (Security Checklist)

| # | البند | الحالة |
|---|-------|--------|
| 1 | منع صرافة نفس العملة | ✅ |
| 2 | Parameterized SQL | ✅ |
| 3 | Rate ثابت من Config (غير قابل للتلاعب) | ✅ |
| 4 | IDOR محمي (user من Token) | ✅ |
| 5 | Mass assignment protection | ✅ |
| 6 | Rate Limiting (20/دقيقة) | ✅ |
| 7 | Atomic DB (FOR UPDATE) | ✅ |
| 8 | Fee من Config (غير قابل للتلاعب) | ✅ |
| 9 | Audit logging | ✅ |
| 10 | الرصيد السالب محمي (WHERE balance >=) | ✅ |
| 11 | قفل بترتيب تصاعدي لمنع Deadlock | ✅ |
| 12 | إعادة محاولة (attempts: 3) | ✅ |
| 13 | No sensitive data in response | ✅ |
| 14 | HTTPS (للإنتاج) | ⏳ |
| 15 | 2FA للمبالغ الكبيرة | ✅ |
