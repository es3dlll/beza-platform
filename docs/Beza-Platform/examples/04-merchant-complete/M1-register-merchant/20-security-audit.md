# 20 - تدقيق الأمان (Security Audit) - تسجيل تاجر (Merchant Registration)

## 1. التحقق من الملكية
```php
// يستخدم التوكن — لا يمكن تزوير user_id
$user = $request->user();
```

## 2. حماية الملفات
```php
// تخزين خارج public_html + التحقق من الصيغة والحجم
$path = $file->store('merchants/{id}/documents', 'local');
'mimes:pdf,jpg,png', 'max:10240'
```

## 3. قائمة التحقق
| # | البند | الحالة |
|---|-------|--------|
| 1 | جميع المدخلات موثقة | ✅ |
| 2 | Parameterized SQL | ✅ |
| 3 | Rate Limiting | ✅ |
| 4 | Authentication (JWT) | ✅ |
| 5 | التحقق من نوع الملفات | ✅ |
| 6 | Mass assignment protection | ✅ |
| 7 | HTTPS (للإنتاج) | ⏳ |
| 8 | التحقق من صحة التوكن | ✅ |
