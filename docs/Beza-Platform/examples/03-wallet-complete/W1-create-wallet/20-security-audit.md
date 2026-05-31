# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. منع إنشاء محافظ مكررة

```php
// ❌ خطأ: عدم التحقق من المحافظ المسبقة
Wallet::create([...]); // قد ينشئ محفظة مكررة

// ✅ صحيح: التحقق المسبق + UNIQUE Constraint
if ($user->wallets()->exists()) {
    throw new WalletsAlreadyExistException();
}
Wallet::create([...]); // UNIQUE(user_id, currency) يمنع التكرار
```

## 2. Mass Assignment

```php
// ❌ خطأ: السماح بكل الحقول
Wallet::create($request->all());

// ✅ صحيح: تحديد الحقول المسموحة
Wallet::create([
    'user_id'       => $user->id,
    'currency'      => 'SYP',
    'wallet_number' => $number,
    'balance'       => 0.00,
    'is_active'     => true,
]);
```

## 3. SQL Injection

```php
// ❌ خطأ: استخدام interpolation
DB::statement("INSERT INTO wallets VALUES ({$userId}, ...)");

// ✅ صحيح: Parameter binding
Wallet::create(['user_id' => $user->id, ...]);
```

## 4. IDOR (لا ينطبق لأن العملية تلقائية)

لا يوجد API لإنشاء المحافظ، ولا يمكن للمستخدم التحكم في user_id.

## 5. Rate Limiting

```php
// routes/api.php — على التسجيل فقط
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:10,1'); // 10 محاولات في الدقيقة
```

## 6. التحقق من التفرد

| المستوى | التقنية |
|----------|---------|
| Application | `$user->wallets()->exists()` |
| Database | `UNIQUE(user_id, currency)` |
| Database | `UNIQUE(wallet_number)` |

## 7. حماية wallet_number

رقم المحفظة:
- لا يمكن تخمينه (12 رقم عشوائي)
- مبدوء ببادئة ثابتة (62/63)
- فريد عالمياً في النظام

## 8. Audit Logging

```php
Log::info('تم إنشاء محفظة جديدة', [
    'user_id'       => $user->id,
    'syp_number'    => $sypNumber,
    'usd_number'    => $usdNumber,
    'bonus'         => 5.00,
    'ip'            => request()->ip(),
]);
```

## 9. قائمة التحقق الأمني (Security Checklist)

| # | البند | الحالة |
|---|-------|--------|
| 1 | UNIQUE(user_id, currency) | ✅ |
| 2 | UNIQUE(wallet_number) | ✅ |
| 3 | التحقق من نشاط المستخدم | ✅ |
| 4 | التحقق من المحافظ المسبقة | ✅ |
| 5 | Mass assignment protection | ✅ |
| 6 | Parameterized SQL | ✅ |
| 7 | Rate Limiting على التسجيل | ✅ |
| 8 | Audit logging | ✅ |
| 9 | الهدية ضمن DB::transaction | ✅ |
| 10 | أرقام محافظ غير قابلة للتخمين | ✅ |
| 11 | PIN مشفر (Bcrypt) | ✅ |
| 12 | لا يمكن إنشاء محفظة يدوياً | ✅ (لا يوجد API) |
