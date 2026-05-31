# 07 - كل قواعد التحقق + أسبابها (Validation Rules)

## BalanceRequest — لا يوجد Body

API من نوع GET ولا يحتوي Body، لذلك لا يوجد Form Request.

## التحقق يتم عبر:

1. **Middleware**: `auth:api` — يضمن أن المستخدم موثّق
2. **Middleware**: `throttle:60,1` — 60 طلب في الدقيقة (كافٍ لعرض الرصيد)
3. **Route Binding**: المستخدم من `$request->user()` — لا يمكن تزويره

## التحقق الإضافي (في BalanceService)

```php
// في BalanceService

// 1. التحقق من وجود المحافظ
$wallets = $user->wallets()->whereIn('currency', ['SYP', 'USD'])->get();

if ($wallets->isEmpty()) {
    // المستخدم ليس لديه محافظ — خطأ
    throw new WalletsNotFoundException();
}

// 2. تحقق إضافي — المحفظة نشطة
foreach ($wallets as $wallet) {
    if (!$wallet->is_active) {
        Log::warning('محفظة غير نشطة في طلب الرصيد', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
        ]);
    }
}
```

## Response Structure (بدون Body Validation)

```json
{
    "success": true,
    "data": {
        "syp": {
            "balance": 150000.00,
            "frozen": 5000.00,
            "available": 145000.00,
            "wallet_number": "621234567890"
        },
        "usd": {
            "balance": 500.00,
            "frozen": 0.00,
            "available": 500.00,
            "wallet_number": "631234567890"
        }
    }
}
```

## ملخص التحقق

| النوع | أين يتم | الترتيب |
|-------|---------|---------|
| Authentication | Middleware (auth:api) | 1 |
| Rate Limiting | Middleware (throttle:60,1) | 2 |
| Wallet Existence | BalanceService | 3 |
| Wallet Active | BalanceService | 4 |
| Cache | BalanceService | Performance |
