# 20 - أمان عملية التنصيب (Security Audit)

## 1. تعطيل المثبت بعد الإكمال

```php
// بعد نجاح التنصيب، يُكتب INSTALLER_LOCKED=true في .env
// هذا يمنع أي شخص من إعادة تشغيل المثبت

$this->configurator->lockInstaller();

// التحقق عند كل طلب
if (env('INSTALLER_LOCKED') === true) {
    abort(403, 'تم إكمال التنصيب مسبقاً');
}
```

## 2. حماية مسارات المثبت

```php
// في routes/web.php — المثبت متاح فقط إذا كان غير مقفول
// يمكن إضافة Middleware للتحقق

// app/Http/Middleware/CheckInstallerEnabled.php
class CheckInstallerEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (env('INSTALLER_LOCKED') === true) {
            return response()->json([
                'success' => false,
                'message' => 'المثبت معطل',
            ], 403);
        }
        return $next($request);
    }
}
```

## 3. التحقق من جميع المدخلات

كل خطوة تستخدم Form Request مخصص:

| الخطوة | Form Request | الحقول |
|--------|-------------|--------|
| قاعدة البيانات | `DatabaseRequest` | db_host, db_port, db_database, db_username, db_password |
| البيئة | `EnvironmentRequest` | app_name, app_url, app_env, redis_*, mail_*, queue_connection |
| المشرف | `AdminUserRequest` | name, email, phone, password |

```php
// منع SQL injection في أسماء قواعد البيانات
'db_database' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_]+$/'],
```

## 4. حماية كلمة المرور

```php
// تشفير كلمة مرور المشرف قبل التخزين
$user->password = Hash::make($request->input('password'));

// منع تخزين كلمة المرور في الجلسة
session()->put('install.admin', [
    'name'  => $user->name,
    'email' => $user->email,
    'phone' => $user->phone,
    // لا تخزين لكلمة المرور
]);
```

## 5. لا تسريب لمعلومات التصحيح

```php
// في بيئة الإنتاج، لا تظهر تفاصيل الأخطاء الحساسة
try {
    // ...
} catch (\PDOException $e) {
    Log::error('DB connection error', [
        'error' => $e->getMessage(),
        // لا تسجيل لكلمة المرور
    ]);

    return response()->json([
        'success' => false,
        'message' => 'فشل الاتصال بقاعدة البيانات',
        // لا نرسل تفاصيل PDO للمستخدم
    ], 422);
}
```

## 6. منع الوصول بعد الإكمال (حتى مع معرفة المسار)

```php
// حتى لو عرف المهاجم مسار /install، لا يمكنه الوصول
// لأن INSTALLER_LOCKED=true يمنع كل الطلبات

// بالإضافة، يمكن حذف ملفات المثبت بعد الإكمال:
// php artisan install:cleanup — يحذف InstallerController وملفات الواجهة
```

## 7. حماية الجلسة

```php
// بيانات الجلسة محمية:
session()->put('install.db', [
    'host'     => $host,
    'port'     => $port,
    'database' => $database,
    'username' => $username,
    // 'password' => $password,  // لا تخزين في الجلسة!
]);

// استخدام HTTPS يمنع اعتراض الجلسة
```

## 8. منع هجمات CSRF

```php
// بما أن المثبت يستخدم web middleware،
// Laravel يحمي تلقائياً من CSRF عبر VerifyCsrfToken

// routes/web.php — جميع مسارات المثبت محمية بـ CSRF
Route::prefix('install')->group(function () {
    // POST endpoints محمية بـ CSRF token
});
```

## 9. صلاحيات الملفات بعد التنصيب

```php
// بعد الإكمال، يمكن تغيير صلاحيات ملف .env
// chmod 600 .env — فقط المالك يقرأ

// يمكن أيضاً حذف ملفات المثبت لزيادة الأمان
// storage/installer/ — حذف الكاش المؤقت
```

## 10. عدم استخدام بيانات تجريبية

```php
// المثبت لا يضع أي بيانات تجريبية أو افتراضية
// جميع القيم يدخلها المستخدم بنفسه

// حتى اسم قاعدة البيانات غير افتراضي
'db_database' => 'required',  // لا قيمة افتراضية!
```

## 11. قائمة التحقق

| # | البند | الحالة | التفاصيل |
|---|-------|--------|----------|
| 1 | تعطيل المثبت بعد الإكمال | ✅ | `INSTALLER_LOCKED=true` في .env |
| 2 | التحقق من المدخلات | ✅ | Form Requests لجميع الخطوات |
| 3 | منع SQL injection | ✅ | Parameterized + regex |
| 4 | تشفير كلمة المرور | ✅ | `Hash::make` باستخدام Bcrypt |
| 5 | منع تسريب الأخطاء | ✅ | أخطاء عامة للمستخدم + تسجيل داخلي |
| 6 | CSRF protection | ✅ | Laravel web middleware |
| 7 | HTTPS (للإنتاج) | ⏳ | يتطلب إعداد SSL على الخادم |
| 8 | حماية الجلسة | ✅ | بيانات حساسة غير مخزنة في الجلسة |
| 9 | صلاحيات الملفات | ✅ | فحص مسبق للتأكد من الصلاحيات |
| 10 | منع الوصول بعد الإكمال | ✅ | 403 Forbidden بعد التنصيب |
| 11 | لا بيانات تجريبية | ✅ | جميع القيم يدخلها المستخدم |
| 12 | Rate limiting | ⏳ | يمكن إضافة throttle للمسارات |
| 13 | حماية من البوتات | ⏳ | يمكن إضافة CAPTCHA (اختياري) |

## 12. توصيات إضافية

```bash
# بعد التنصيب، قم بما يلي لزيادة الأمان:

# 1. تغيير صلاحيات .env
chmod 600 .env

# 2. حذف ملفات المثبت
rm -rf app/Http/Controllers/Install/
rm -rf app/Services/Install/
rm -rf resources/js/installer/

# 3. إزالة المسارات من web.php
# حذف Route::prefix('install')->... من routes/web.php

# 4. استخدام HTTPS
# تأكد من أن APP_URL يبدأ بـ https://

# 5. تغيير كلمة مرور المشرف بعد أول تسجيل دخول

# 6. تفعيل rate limiting على مسارات المثبت
Route::prefix('install')->middleware('throttle:10,1')->group(function () {
    // ...
});
```
