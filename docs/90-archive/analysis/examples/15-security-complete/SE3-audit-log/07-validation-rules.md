# 07 - قواعد الأحداث المسجلة (Validation Rules)

## قائمة الأحداث الإجبارية

```php
// يجب تسجيل هذه الأحداث دائماً
$mandatoryEvents = [
    // أمان
    'login', 'login_failed', 'logout',
    'pin_changed', 'password_changed',
    'pin_failed', '2fa_enabled', '2fa_disabled',

    // معاملات مالية
    'transfer_created', 'deposit', 'withdraw', 'exchange',

    // محفظة
    'wallet_updated', 'wallet_frozen', 'wallet_unfrozen',

    // KYC
    'kyc_submitted', 'kyc_verified', 'kyc_rejected',

    // مشرف
    'admin_action', 'user_blocked', 'user_suspended',
    'user_verified', 'settings_changed',
];
```

## البيانات المسجلة لكل حدث

| الحدث | البيانات المسجلة |
|-------|-----------------|
| login | ip, device_id, user_agent |
| transfer_created | from_wallet, to_wallet, amount, currency |
| pin_changed | - (no old pin logged) |
| kyc_verified | document_type, admin_id |
| admin_action | action, target_user_id, reason |
| settings_changed | setting_key, old_value, new_value |
| wallet_updated | old_balance, new_balance, currency |

## ما لا يسجل أبداً

```php
// ❌ لا تسجل أبداً:
$neverLog = [
    'password',       // كلمة المرور
    'pin_code',       // PIN
    'two_factor_secret',    // سر 2FA
    'two_factor_recovery_codes', // رموز الاسترداد
    'fcm_token',      // توكن الإشعارات
    'card_number',    // رقم البطاقة
    'cvv',            // CVV
    'expiry_date',    // تاريخ انتهاء البطاقة
];
```
