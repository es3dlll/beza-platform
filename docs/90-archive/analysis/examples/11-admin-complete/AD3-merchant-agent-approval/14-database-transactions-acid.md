# 14 - ACID في الموافقة على التجار

## الموافقة Atomic

```php
DB::transaction(function () use ($merchant, $reviewerId) {
    // 1. تحديث حالة التاجر
    $merchant->update([
        'status'      => 'active',
        'reviewed_by' => $reviewerId,
        'reviewed_at' => now(),
    ]);

    // 2. تحديث صلاحية المستخدم
    $merchant->user()->update(['is_merchant' => true]);

    // 3. تحديث حالة المستندات
    $merchant->documents()->update(['status' => 'approved']);

    // 4. تسجيل النشاط
    AdminActivityLog::create([
        'admin_id'    => $reviewerId,
        'action'      => 'approve_merchant',
        'target_type' => 'merchant',
        'target_id'   => $merchant->id,
    ]);
});
// → كل شيء ينجح أو كل شيء يفشل
```

## الرفض Atomic

```php
DB::transaction(function () use ($merchant, $reason, $reviewerId) {
    $merchant->update([
        'status'           => 'rejected',
        'rejection_reason' => $reason,
        'reviewed_by'      => $reviewerId,
        'reviewed_at'      => now(),
    ]);

    AdminActivityLog::create([
        'admin_id'    => $reviewerId,
        'action'      => 'reject_merchant',
        'target_id'   => $merchant->id,
        'metadata'    => ['reason' => $reason],
    ]);
});
```

## Why Atomic?

| السيناريو | بدون Atomic | مع Atomic |
|-----------|------------|-----------|
| وافقت على التاجر لكن فشل تحديث user.is_merchant | تاجر active لكن ليس له صلاحيات → غير قادر على البيع | كل شيء يتراجع → لا مشكلة |
| رفضت التاجر لكن فشل تسجيل الرفض | التاجر يبقى pending | يتراجع ← يبقى pending |
