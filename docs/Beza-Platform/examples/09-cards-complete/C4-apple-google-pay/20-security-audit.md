# 20 - تدقيق الأمان (Security Audit) - Apple Pay و Google Pay

## 1. Token Authentication
JWT Token + device verification required.

## 2. Token Security
```php
// Wallet tokens are cryptographically signed
$token = encrypt(json_encode(['card_id' => $card->id, 'device_id' => $deviceId]));
```

## 3. Device Binding
```php
// Token is bound to specific device — stolen token unusable on other devices
$token = WalletToken::where('token', $token)->where('device_id', $deviceId)->firstOrFail();
```

## 4. Rate Limiting
```php
// Prevent token enumeration
Route::middleware('throttle:15,1')->group(function () { ... });
```

## قائمة التحقق الأمني (Security Checklist)
| # | Item | Status |
|---|------|--------|
| 1 | Input validation | ✅ |
| 2 | Parameterized SQL | ✅ |
| 3 | Rate limiting | ✅ |
| 4 | Device binding | ✅ |
| 5 | Token encryption | ✅ |
| 6 | Payment token expiry | ✅ |
| 7 | HTTPS (production) | ⏳ |
| 8 | Apple Pay/Google Pay tokenization | ✅ |
