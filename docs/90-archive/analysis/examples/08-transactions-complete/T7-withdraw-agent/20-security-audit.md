# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. PIN — التخزين والتحقق

```php
// ✅ تخزين مشفر
$user->pin_code = Hash::make($request->pin);

// ✅ التحقق
if (!Hash::check($pin, $user->pin_code)) {
    throw new InvalidPinException();
}
```

| الممارسة | الحالة |
|----------|--------|
| استخدام Bcrypt/Argon2 | ✅ |
| Salt تلقائي | ✅ |
| عدم إرجاع PIN في أي API | ✅ |

## 2. IDOR (Insecure Direct Object Reference)

```php
// ✅ صحيح: المستخدم المصادق فقط
$user = $request->user(); // من Auth Token — لا يمكن تزويره
```

## 3. SQL Injection

```php
// ✅ صحيح: Parameter binding
DB::update('UPDATE wallets SET balance = balance - ? WHERE id = ?', [$amount, $walletId]);
```

## 4. Rate Limiting

```php
Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::post('/withdraw/agent', ...);
});
```

## 5. Mass Assignment

```php
// ✅ صحيح: تحديد الحقول المسموحة
Transaction::create([
    'from_wallet_id'  => $wallet->id,
    'amount'          => $amount,
    'type'            => 'agent_cash_out',
    'status'          => 'completed',
    'reference_number'=> Transaction::generateReferenceNumber(),
    'completed_at'    => now(),
]);
```

## 6. Race Condition (TOCTOU)

```php
// ✅ صحيح: WHERE balance >= amount في نفس استعلام التحديث
DB::update(
    'UPDATE wallets SET balance = balance - ? WHERE id = ? AND balance >= ?',
    [$amount, $walletId, $amount]
);
```

## 7. Logging & Audit

```php
Log::info('سحب وكيل attempt', [
    'user_id' => $user->id,
    'amount'  => $amount,
    'ip'      => request()->ip(),
]);
```

## 8. قائمة التحقق الأمني (Security Checklist)

| # | البند | الحالة |
|---|-------|--------|
| 1 | PIN مشفر (Bcrypt) | ✅ |
| 2 | جميع المدخلات موثقة | ✅ |
| 3 | Parameterized SQL | ✅ |
| 4 | Rate Limiting (30/دقيقة) | ✅ |
| 5 | Authentication (JWT) | ✅ |
| 6 | IDOR محمي | ✅ |
| 7 | Atomic DB (FOR UPDATE) | ✅ |
| 8 | No sensitive data in response | ✅ |
| 9 | Audit logging | ✅ |
| 10 | Mass assignment protection | ✅ |
| 11 | HTTPS (للإنتاج) | ⏳ |
| 12 | 2FA (مستقبلاً) | ⏳ (Pending) |
| 13 | PIN brute-force lockout | ⏳ (Pending) |
