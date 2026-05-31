# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. منع Double Reward

```php
// ✅ قفل المكافأة قبل الصرف
$reward = ReferralReward::where('id', $rewardId)
    ->where('status', 'pending')
    ->lockForUpdate()
    ->firstOrFail();
```

## 2. منع التلاعب بكود الإحالة

```php
// ✅ كود عشوائي 8 أحرف — لا يمكن تخمينه
// ✅ unique constraint على code
```

## 3. منع Self-Referral

```php
if ($code->user_id === $user->id) {
    throw new SelfReferralException();
}
```

## 4. Rate Limiting

```php
// منع Brute Force على أكواد الإحالة
Route::middleware('throttle:10,1')->post('/referral/claim', ...);
```

## 5. قائمة التحقق

| # | البند | الحالة |
|---|-------|--------|
| 1 | Double reward prevention | ✅ |
| 2 | Self-referral prevention | ✅ |
| 3 | Unique random codes | ✅ |
| 4 | Rate limiting | ✅ |
| 5 | Atomic reward payment | ✅ |
| 6 | Audit trail | ✅ |
| 7 | Min transaction condition | ✅ |
