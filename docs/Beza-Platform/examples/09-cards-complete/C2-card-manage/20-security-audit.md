# 20 - تدقيق الأمان (Security Audit) - إدارة البطاقة

## 1. Authentication
JWT Token required for card freeze/cancel/update.

## 2. IDOR Prevention
```php
// Verify ownership on every management action
$card = Card::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
```

## 3. Status Transition Validation
```php
// Only allow valid status transitions (active -> frozen, frozen -> active)
$allowed = ['active' => ['frozen'], 'frozen' => ['active', 'cancelled']];
```

## 4. Rate Limiting
```php
// Prevent rapid status toggle attacks
Route::middleware('throttle:20,1')->group(function () { ... });
```

## قائمة التحقق الأمني (Security Checklist)
| # | Item | Status |
|---|------|--------|
| 1 | Input validation | ✅ |
| 2 | Parameterized SQL | ✅ |
| 3 | Rate limiting | ✅ |
| 4 | IDOR protection | ✅ |
| 5 | Status transition validation | ✅ |
| 6 | Audit logging | ✅ |
| 7 | HTTPS (production) | ⏳ |
| 8 | Webhook for card events | ✅ |
