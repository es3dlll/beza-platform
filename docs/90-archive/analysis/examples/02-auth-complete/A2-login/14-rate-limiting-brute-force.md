# 14 - منع الهجمات وهندسة المعدل (Rate Limiting & Brute Force)

## Rate Limiting

```php
<?php
// في Route

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');
    // 10 محاولات كحد أقصى في الدقيقة
```

## قفل الحساب (Account Lockout)

```php
<?php
// في AuthService

private const MAX_ATTEMPTS = 5;
private const LOCKOUT_DURATION = 15; // دقيقة

public function login(string $phone, string $password, ...): array
{
    $user = User::where('phone', $phone)->first();

    if ($user && $this->isAccountLocked($user)) {
        $remaining = $this->getLockRemainingMinutes($user);
        throw new AccountLockedException($remaining);
    }

    if (!$user || !Hash::check($password, $user->password)) {
        if ($user) {
            $this->incrementFailedAttempts($user);
        }
        $this->logFailedAttempt($phone);
        throw new InvalidCredentialsException();
    }

    $this->clearFailedAttempts($user);

    // ... rest
}
```

## تدفق قفل الحساب

```
محاولة 1: fail → attempts=1
محاولة 2: fail → attempts=2
محاولة 3: fail → attempts=3
محاولة 4: fail → attempts=4
محاولة 5: fail → attempts=5 → LOCK (15 دقيقة)
محاولة 6: رد فوري — "حساب مقفل"
بعد 15 دقيقة: attempts=0 → يمكن المحاولة مجدداً
```

## تخزين البيانات في Redis

```php
<?php
// مفتاح Redis: login_attempts_{user_id}
// القيمة: { attempts: 5, locked_at: timestamp }
// TTL: 15 دقيقة
```

## منع Timing Attack

```php
<?php
// دائماً نستخدم نفس الرسالة العامة
// "بيانات الدخول غير صحيحة"
// سواء المستخدم موجود أو لا
// يمنع المهاجم من معرفة الأرقام المسجلة

if (!$user) {
    throw new InvalidCredentialsException(); // 401
}

if (!Hash::check($password, $user->password)) {
    throw new InvalidCredentialsException(); // 401 — نفس الرسالة
}
```

## منع Credential Stuffing

| الإجراء | التفصيل |
|---------|---------|
| throttle:10,1 | حد عام لجميع محاولات الدخول |
| Account lockout | 5 محاولات فاشلة → قفل 15 دقيقة |
| Same error message | لا نميز بين الخطأ (رقم/كلمة سر) |
| Redis storage | سريع وفعال للتخزين المؤقت |
| Logging | تسجيل كل محاولة فاشلة للتحليل |
