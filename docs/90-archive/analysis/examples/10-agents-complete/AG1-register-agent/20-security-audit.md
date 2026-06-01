# 20 - أمان العملية خطوة بخطوة (Security Audit)

## 1. المصادقة (Authentication)
مطلوب توكن JWT. فقط المستخدمون الموثّقون يمكنهم التقديم.

## 2. أمان المستندات (Document Security)
```php
// المستندات تُخزن خارج public_html + التحقق من نوع MIME
$path = $file->store('agent-documents/' . $user->id, 'local');
'mimes:pdf,jpg,png', 'max:10240'
```

## 3. منع التكرار (Duplicate Request Prevention)
```php
// طلب واحد معلق لكل مستخدم
$existing = AgentRequest::where('user_id', auth()->id())
    ->whereIn('status', ['pending', 'approved'])->exists();
```

## 4. تحديد المعدل (Rate Limiting)
```php
// منع إرسال طلبات متكررة
Route::middleware('throttle:3,1')->group(function () { ... });
```

## قائمة التحقق الأمني (Security Checklist)

| # | البند | الحالة |
|---|-------|--------|
| 1 | التحقق من المدخلات (Input validation) | ✅ |
| 2 | استعلامات SQL آمنة (Parameterized) | ✅ |
| 3 | تحديد المعدل (Rate limiting) | ✅ |
| 4 | منع الطلبات المكررة | ✅ |
| 5 | التحقق من نوع الملف (File type) | ✅ |
| 6 | أمان تخزين المستندات | ✅ |
| 7 | HTTPS (في الإنتاج) | ⏳ |
| 8 | موافقة المشرف مطلوبة | ✅ |
