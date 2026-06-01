# 14 - ACID في إدارة المستخدمين

## Soft Delete مع الاحتفاظ بالعلاقات

```php
DB::transaction(function () use ($user, $adminId) {
    // 1. تعطيل المحافظ (لكن لا نحذفها)
    $user->wallets()->update(['is_active' => false]);

    // 2. تعطيل التاجر/الوكيل إن وجد
    if ($user->merchant) {
        $user->merchant->update(['status' => 'disabled']);
    }
    if ($user->agent) {
        $user->agent->update(['status' => 'disabled']);
    }

    // 3. حذف ناعم
    $user->delete(); // sets deleted_at

    // 4. إبطال جميع التوكنات
    $user->tokens()->delete();

    // 5. تسجيل النشاط
    AdminActivityLog::create([...]);
});
// → كل شيء ينجح أو كل شيء يفشل
```

## تعليق المستخدم — ضمان Atomicity

```php
DB::transaction(function () use ($user, $reason) {
    $user->update(['status' => 'suspended']);
    $user->tokens()->delete();

    if ($reason) {
        AdminActivityLog::create([
            'admin_id' => auth()->id(),
            'action'   => 'suspend',
            'target_type' => 'user',
            'target_id'   => $user->id,
            'metadata'    => ['reason' => $reason],
        ]);
    }
});
```

## FOR UPDATE غير مطلوب هنا

إدارة المستخدمين عمليات إدارية قليلة التزامن — لا تحتاج Pessimistic Lock.

## أمان الحذف الناعم (Soft Delete)

```php
// التأكد من أن soft deleted users لا يظهرون في أي مكان
public function scopeActive($query)
{
    return $query->whereNull('deleted_at');
}

// دالة تسجيل الدخول تتحقق
if ($user->deleted_at) {
    throw new AccountDeletedException();
}
```
