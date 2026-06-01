# Middleware — ApiWapAuth

الملف المقترح: `backend/app/Http/Middleware/ApiWapAuth.php`

## المنطق
1. استخراج JWT من `Cookie: token=...` (أولوية أولى)
2. إذا لم يوجد في Cookie → جرب `Authorization: Bearer ...`
3. فك تشفير JWT والتحقق من التوقيع
4. التحقق من `jti` في القائمة السوداء
5. التحقق من صلاحية `role` إذا تطلب المسار دوراً محدداً
6. تعيين `auth()->setUser($user)` للمستخدم
7. تمرير الطلب إلى الـ Controller

## التسجيل في Kernel
```php
// backend/bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'auth.wap' => \App\Http\Middleware\ApiWapAuth::class,
    ]);
})
```

## ملاحظة
يستخدم Middleware قائمة بـ `Cookie` parser مخصصة لأن Laravel لا يقرأ الـ Cookie تلقائياً في API requests. يمكن استخدام حزمة `laravel-cookie-consent` أو parser يدوي.
