# 20 - أمان النزاعات (Security Audit)

## 1. صلاحية المستخدم والمشرف

```php
// ✅ المستخدم: يمكنه تقديم نزاع فقط على معاملاته
Route::middleware('auth:api')->post('/support/disputes', ...);

// ✅ المشرف: يمكنه رؤية وحل جميع النزاعات
Route::middleware(['auth:api', 'admin'])->prefix('admin/disputes')->group(...);
```

## 2. IDOR Protection

```php
// ✅ المستخدم لا يمكنه تقديم نزاع على معاملة ليست له
$transaction = Transaction::findOrFail($transactionId);
if ($transaction->fromWallet->user_id !== $user->id
    && $transaction->toWallet->user_id !== $user->id) {
    throw new UnauthorizedException();
}
```

## 3. حماية الملفات

```php
// ✅ تخزين الأدلة في private storage
Storage::disk('private')->put('disputes/' . $file->hashName(), $file);

// ✅ الوصول عبر middleware مع المصادقة
Route::get('/dispute-evidence/{id}', [DisputeEvidenceController::class, 'download'])
    ->middleware('auth:api');
```

## 4. Audit Logging

```php
// ✅ تسجيل كل إجراء على النزاع
Log::info('Dispute resolved', [
    'dispute_id' => $disputeId,
    'resolution' => $resolution,
    'by_admin'   => $adminId,
]);
```

## 5. قائمة التحقق

| # | البند | الحالة |
|---|-------|--------|
| 1 | المستخدم فقط معاملاته | ✅ |
| 2 | المشرف فقط يحل النزاعات | ✅ |
| 3 | IDOR protection | ✅ |
| 4 | الملفات في private storage | ✅ |
| 5 | التحقق من الرصيد قبل refund | ✅ |
| 6 | Audit logging | ✅ |
| 7 | إغلاق تلقائي بعد 48 ساعة | ✅ |
| 8 | Atomicity في الاسترجاع | ✅ |
| 9 | Rate limiting | ✅ |
| 10 | Validation على جميع المدخلات | ✅ |
