# 14 - ACID + الأقفال + الـ Race Conditions

## تحديات KYC

### مشكلة: رفع وثائق متزامن
المستخدم يرفع الوثائق مرتين بنفس اللحظة — يجب منع إنشاء سجلات مكررة.

```php
DB::transaction(function () use ($user) {
    // قفل المستخدم
    User::where('id', $user->id)->lockForUpdate()->first();

    // تحقق من حالة KYC داخل المعاملة
    if (in_array($user->kyc_status, ['pending', 'verified'])) {
        throw new KycAlreadySubmittedException($user->kyc_status);
    }

    // إنشاء الوثائق وتحديث الحالة
    foreach ($files as $category => $file) {
        KycDocument::create([...]);
    }
    $user->update(['kyc_status' => 'pending']);
});
```

### مشكلة: مراجعة Admin متزامنة
مشرفان يحاولان مراجعة نفس المستخدم:

```php
DB::transaction(function () use ($user) {
    User::where('id', $user->id)->lockForUpdate()->first();
    $user->refresh();

    if ($user->kyc_status !== 'pending') {
        throw new \RuntimeException('تمت مراجعة هذا المستخدم بالفعل');
    }

    // مراجعة ...
});
```

## Atomicity في رفع الملفات

```php
DB::transaction(function () use ($user, $files) {
    // رفع جميع الملفات + تحديث الحالة
    // إذا فشل رفع ملف → ROLLBACK لكل شيء
}, attempts: 2);
```

## Consistency

| القاعدة | مكان التحقق |
|---------|-------------|
| kyc_status must be valid enum | MySQL ENUM + Laravel cast |
| user cannot resubmit while pending | Application + DB lock |
| files must exist in storage | Application (before DB write) |
