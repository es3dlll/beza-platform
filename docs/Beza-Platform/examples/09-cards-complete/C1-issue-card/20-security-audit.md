# 20 - تدقيق الأمان (Security Audit) - إصدار البطاقة

## 1. Authentication
JWT Token required for all card operations.

## 2. IDOR Prevention
```php
// Always verify card belongs to authenticated user
$card = Card::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
```

## 3. Card Number Security
```php
// PAN masked in responses — only last 4 digits visible
protected $hidden = ['pan', 'cvv', 'pin'];
```

## 4. Rate Limiting
```php
// Prevent mass card creation
Route::middleware('throttle:10,1')->group(function () { ... });
```

## قائمة التحقق الأمني (Security Checklist)
| # | Item | Status |
|---|------|--------|
| 1 | Input validation | ✅ |
| 2 | Parameterized SQL | ✅ |
| 3 | Rate limiting | ✅ |
| 4 | PAN masking | ✅ |
| 5 | IDOR protection | ✅ |
| 6 | CVV not stored | ✅ |
| 7 | HTTPS (production) | ⏳ |
| 8 | Audit logging | ✅ |
| 9 | Card BIN validation | ✅ |
