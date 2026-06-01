# 04 - جداول 2FA (Database Relationships)

## هيكل جدول users لحقول 2FA

```php
Schema::table('users', function (Blueprint $table) {
    $table->text('two_factor_secret')->nullable()->after('pin_code');
    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
    $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
});
```

## العلاقات

```
┌──────────────────────────────────────────────────────────┐
│                       users                               │
│──────────────────────────────────────────────────────────│
│ id                     │ BIGINT     │ PK                 │
│ two_factor_secret      │ TEXT       │ Encrypted (NULL=غير مفعل) │
│ two_factor_recovery_codes │ TEXT    │ JSON array, Encrypted     │
│ two_factor_confirmed_at│ TIMESTAMP  │ تاريخ التفعيل             │
│──────────────────────────────────────────────────────────│
│ two_factor_secret:  تشفير AES-256-CBC (Laravel encrypt)   │
│ two_factor_recovery_codes: JSON مشفر ["code1","code2",..] │
│                                                           │
│ لا يوجد جدول منفصل — كل بيانات 2FA في جدول users نفسه      │
└──────────────────────────────────────────────────────────┘
```

## لماذا التخزين المشفر؟

```php
// ❌ خطأ: تخزين النص الصريح
$user->two_factor_secret = $secret;

// ✅ صحيح: تخزين مشفر
$user->two_factor_secret = encrypt($secret);

// ✅ صحيح: إخفاء من JSON response
protected $hidden = [
    'two_factor_secret',
    'two_factor_recovery_codes',
];
```
