# 20 - أمان الموافقة على التجار والوكلاء

## 1. صلاحية المشرف (Authorization)

```php
// ✅ Admin Middleware مطلوب لكل الإجراءات
Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::post('/merchants/{id}/approve', ...);
    Route::post('/merchants/{id}/reject', ...);
});
```

## 2. منع IDOR

```php
// ✅ المستخدم العادي لا يمكنه الموافقة/الرفض
// ✅ لا يمكن لمشرف الموافقة على طلب ليس ملكه — لا يوجد IDOR هنا لأن
//   الـ id هو merchant id وليس user id
```

## 3. حماية الملفات (المستندات)

```php
// ✅ تخزين الملفات خارج المجال العام
Storage::disk('private')->put('documents/merchants/' . $file->hashName(), $file);

// ✅ الوصول عبر middleware مع صلاحية المشرف
Route::get('/admin/documents/{id}', [DocumentController::class, 'view'])
    ->middleware(['auth:api', 'admin']);
```

## 4. Audit Logging

```php
// ✅ تسجيل كل موافقة ورفض مع سبب الرفض والتاريخ والمشرف
AdminActivityLog::create([
    'admin_id' => $reviewerId,
    'action'   => $action, // approve_merchant, reject_merchant
    'metadata' => ['reason' => $reason ?? null],
]);
```

## 5. قائمة التحقق الأمني

| # | البند | الحالة |
|---|-------|--------|
| 1 | Admin middleware | ✅ |
| 2 | رفض يتطلب سبب (validation) | ✅ |
| 3 | Atomicity (DB transaction) | ✅ |
| 4 | Audit log لكل إجراء | ✅ |
| 5 | حماية الملفات (private storage) | ✅ |
| 6 | التحقق من حالة الطلب (pending) | ✅ |
| 7 | منع التكرار (سجل تجاري فريد) | ✅ |
| 8 | التحقق من KYC قبل الموافقة | ✅ |
| 9 | Rate limiting | ✅ |
