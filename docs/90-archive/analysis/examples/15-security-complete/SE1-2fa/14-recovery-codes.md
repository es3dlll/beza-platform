# 14 - رموز الاسترداد (Recovery Codes)

## التوليد

```php
public function generateRecoveryCodes(): array
{
    $codes = [];
    for ($i = 0; $i < 8; $i++) {
        $codes[] = strtoupper(
            implode('-', [
                substr(bin2hex(random_bytes(3)), 0, 4),
                substr(bin2hex(random_bytes(3)), 0, 4),
                substr(bin2hex(random_bytes(3)), 0, 4),
            ])
        );
    }
    return $codes;
}
// مثال: "A3F2-B7D1-9E4C"
```

## التخزين

```php
// تخزين مشفر
$user->two_factor_recovery_codes = encrypt(json_encode($codes));
```

## الاستخدام

```php
public function useRecoveryCode(User $user, string $code): bool
{
    $codes = $user->recoveryCodes(); // مفكوك التشفير

    if (!$codes) return false;

    $index = array_search(strtoupper($code), $codes);

    if ($index === false) return false;

    // إزالة الرمز المستخدم
    unset($codes[$index]);
    $user->setRecoveryCodes(array_values($codes));
    $user->save();

    return true;
}
```

## عرض رموز الاسترداد

```php
// عرض رموز الاسترداد بعد التفعيل (مرة واحدة فقط)
public function showRecoveryCodes(Request $request): JsonResponse
{
    $user = $request->user();

    if (!$user->hasTwoFactorEnabled()) {
        throw new TwoFactorNotEnabledException();
    }

    return response()->json([
        'success' => true,
        'data' => [
            'recovery_codes' => $user->recoveryCodes(),
            'remaining' => count($user->recoveryCodes() ?? []),
        ],
    ]);
}
```

## إعادة توليد رموز الاسترداد

```php
public function regenerateRecoveryCodes(Request $request): JsonResponse
{
    $user = $request->user();

    $codes = app(TwoFactorService::class)->generateRecoveryCodes();
    $user->setRecoveryCodes($codes);
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'تم توليد رموز استرداد جديدة',
        'data' => ['recovery_codes' => $codes],
    ]);
}
```
