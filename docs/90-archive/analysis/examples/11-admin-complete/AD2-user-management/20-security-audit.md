# 20 - أمان إدارة المستخدمين (Security Audit)

## 1. صلاحية المشرف (Authorization)

```php
// ✅ Admin Middleware مطلوب لكل الإجراءات
Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::apiResource('/admin/users', AdminUserController::class);
});

// ❌ مستخدم عادي لا يمكنه الوصول
if (!$request->user()->is_admin) {
    abort(403, 'Admin only');
}
```

## 2. IDOR Protection

```php
// ✅ البحث بـ id مع ضمان وجود المستخدم
$user = User::findOrFail($id);

// ✅ لا يمكن تعديل مستخدم آخر خارج الصلاحية
// كل عملية تتحقق من هوية المستخدم المستهدف فقط
```

## 3. حماية من التعليق/الحظر

```php
// ✅ لا يمكن تعليق/حظر المشرفين
if ($targetUser->is_admin) {
    throw new CannotBlockAdminException();
}

// ✅ لا يمكن حذف الذات
if ($targetId === auth()->id()) {
    throw new CannotDeleteSelfException();
}
```

## 4. إبطال الجلسات النشطة

```php
// ✅ عند التعليق أو الحظر — إبطال جميع التوكنات
// JWT: إبطال جميع التوكنات بزيادة رقم إصدار التوكن;
```

## 5. Soft Delete Protection

```php
// ✅ جميع الاستعلامات تستثني المحذوفين
User::whereNull('deleted_at')->get();

// ✅ المعاملات تبقى مع SET NULL
// ✅ المحافظ تُعطَّل فقط لا تُحذف
```

## 6. قائمة التحقق الأمني

| # | البند | الحالة |
|---|-------|--------|
| 1 | Admin middleware | ✅ |
| 2 | IDOR protection | ✅ |
| 3 | منع تعليق المشرفين | ✅ |
| 4 | منع حذف الذات | ✅ |
| 5 | إبطال التوكنات عند التعليق | ✅ |
| 6 | Soft delete (ليس hard) | ✅ |
| 7 | Audit logging لكل إجراء | ✅ |
| 8 | Rate limiting (60/دقيقة) | ✅ |
| 9 | Validation على المدخلات | ✅ |
| 10 | SQL injection protection | ✅ |
