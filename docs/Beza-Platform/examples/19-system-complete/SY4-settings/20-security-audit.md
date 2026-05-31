# 20 - تدقيق أمني: صلاحيات المسؤول، التحقق من المدخلات، حماية XSS (Security Audit)

## نظرة عامة (Overview)

تدقيق أمني كامل لنظام SY4-ssettings. يغطي: صلاحيات الوصول، التحقق من المدخلات، الحماية من الهجمات، والتخزين الآمن.

```php
// // SY4-settings هو واحد من أكثر الأنظمة حساسية
// // لأنه يتحكم في إعدادات المنصة بأكملها
// // أي ثغرة هنا تؤثر على كل شيء
```

## 1. صلاحيات الوصول (Access Control)

```php
// // المستوى 1: المصادقة (Authentication) - auth:api
// // جميع المسارات محمية بـ JWT

Route::middleware(['auth:api'])->group(function () {
    // // فقط المستخدمون المسجلون (بأي دور)
});

// // المستوى 2: التفويض (Authorization) - admin
// // صلاحية المسؤول فقط

Route::middleware(['auth:api', 'admin'])->group(function () {
    Route::get('/admin/system/settings', [SystemSettingsController::class, 'index']);
    Route::put('/admin/system/settings/{group}', [SystemSettingsController::class, 'update']);
});

// // تنفيذ middleware admin
// // ملف: app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // // التحقق من أن المستخدم المسجل لديه صلاحية مسؤول
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. هذه الميزة للمسؤولين فقط',
            ], 403);
        }

        return $next($request);
    }
}
```

## 2. التحقق من المدخلات (Input Validation)

```php
// // كل نقطة نهاية API تستخدم SettingsValidator
// // مع قواعد صارمة لكل مجموعة

// // التحقق من:
// // 1. نوع البيانات (numeric, string, boolean, json)
// // 2. الحدود (min, max)
// // 3. القيم المسموحة (in: ar, en)
// // 4. الطول (min:2, max:100)
// // 5. التنسيق (timezone, url, email)

// // مثال: التحقق من منطقة زمنية
'timezone' => 'required|string|timezone'
// // Laravel تتحقق من أن القيمة موجودة في
// // قائمة المناطق الزمنية المعروفة

// // مثال: التحقق من URL آمن
'app_logo' => 'nullable|string|url|max:500'
// // url تتحقق من صحة الرابط
// // max:500 تمنع الروابط الطويلة جداً
```

## 3. الحماية من XSS (Cross-Site Scripting)

```php
// // الإعدادات النصية قد تحتوي على HTML
// // (مثل رسالة الصيانة أو وصف التطبيق)
// // هذه القيم تعرض في لوحة الإدارة وتطبيق Flutter

// // الحماية: Sanitization عند العرض
// // في React:
function sanitizeHtml(input: string): string {
    return input.replace(/<[^>]*>/g, '');
}

// // في Laravel (عند التخزين):
public function sanitizeValue(string $value): string
{
    // // إزالة HTML tags من الإعدادات النصية
    return strip_tags($value);
}

// // تطبيق sanitize في SettingsService
public function setGroup(string $group, array $data): void
{
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $data[$key] = $this->sanitizeValue($value);
        }
    }
    // // متابعة التحديث...
}

// // لكن: إعدادات JSON (مثل SMTP) يجب ألا تمر بـ strip_tags
// // لأنها تحتوي على JSON وليس HTML
private function shouldSanitize(string $type): bool
{
    return in_array($type, ['string', 'text']);
}
```

## 4. حماية البيانات الحساسة (Sensitive Data)

```php
// // إعدادات SMTP تحتوي على كلمة مرور البريد
// // يجب حمايتها بشكل خاص

// // 1. تشفير كلمة مرور SMTP عند التخزين
public function encryptSmtpPassword(array $smtpConfig): array
{
    if (!empty($smtpConfig['password'])) {
        $smtpConfig['password'] = encrypt($smtpConfig['password']);
    }
    return $smtpConfig;
}

// // 2. فك التشفير عند القراءة
public function decryptSmtpPassword(array $smtpConfig): array
{
    if (!empty($smtpConfig['password'])) {
        try {
            $smtpConfig['password'] = decrypt($smtpConfig['password']);
        } catch (\Exception $e) {
            // // كلمة المرور قديمة أو تالفة
            $smtpConfig['password'] = '';
        }
    }
    return $smtpConfig;
}

// // 3. إخفاء كلمة المرور في الاستجابة
public function maskSmtpPassword(array $smtpConfig): array
{
    if (!empty($smtpConfig['password'])) {
        $smtpConfig['password'] = '********';
    }
    return $smtpConfig;
}

// // استخدام هذه الدوال في SettingsService:
public function get(string $key, mixed $default = null): mixed
{
    $value = parent::get($key, $default);
    
    // // إخفاء كلمة المرور في الردود
    if ($key === 'mail.smtp' && is_array($value)) {
        $value = $this->maskSmtpPassword($value);
    }
    
    return $value;
}
```

## 5. منع SQL Injection

```php
// // الإعدادات تخزن في قاعدة البيانات باستخدام
// // Eloquent ORM الذي يحمي من SQL Injection تلقائياً

// // آمن:
SystemSetting::where('group', $group)
    ->where('key', $key)
    ->first();

// // آمن:
DB::table('system_settings')->updateOrInsert(
    ['group' => $group, 'key' => $key],
    ['value' => $value]
);

// // غير آمن (لا تستخدم):
// DB::statement("SELECT * FROM system_settings WHERE key = '{$key}'");
```

## 6. منع Mass Assignment

```php
// // موديل SystemSetting يستخدم $fillable
// // لمنع تعيين حقول غير مسموحة

protected $fillable = [
    'group',
    'key',
    'value',
    'type',
    'description',
];

// // هذا يمنع المهاجم من تعيين:
// // - id (لا يمكن تغيير)
// // - created_at (تلقائي)
// // - updated_at (تلقائي)
```

## 7. حماية Rate Limiting

```php
// // منع إرسال طلبات كثيرة جداً لتحديث الإعدادات

Route::middleware(['auth:api', 'admin', 'throttle:60,1'])->group(function () {
    Route::put('/admin/system/settings/{group}', [SystemSettingsController::class, 'update']);
});

// // 60 طلب في الدقيقة كحد أقصى
// // يمنع brute force أو DOS على نقاط النهاية
```

## 8. تدقيق السجلات (Audit Logging)

```php
// // كل تغيير في الإعدادات يجب أن يسجل:

public function logChange(
    string $group,
    array $oldData,
    array $newData,
    int $adminId
): void {
    DB::table('audit_logs')->insert([
        'auditable_type' => 'system_settings',
        'auditable_id'   => 0,
        'event'          => 'setting_updated',
        'old_values'     => json_encode($oldData),
        'new_values'     => json_encode($newData),
        'group'          => $group,
        'admin_id'       => $adminId,
        'ip_address'     => request()->ip(),
        'user_agent'     => request()->userAgent(),
        'created_at'     => now(),
    ]);
}
```

## 9. فحص الأمن الشامل (Security Checklist)

```php
// // ✓ جميع المسارات محمية بـ auth:api (JWT)
// // ✓ جميع المسارات تتطلب صلاحية admin
// // ✓ التحقق من صحة المدخلات لكل مجموعة
// // ✓ Sanitization للقيم النصية (strip_tags)
// // ✓ تشفير كلمات المرور في SMTP
// // ✓ حماية من SQL Injection (Eloquent ORM)
// // ✓ Mass Assignment protection (fillable)
// // ✓ Rate Limiting (60 req/min)
// // ✓ سجل تدقيق لكل تغيير
// // ✓ كلمة المرور مخفية في الاستجابة
// // - لا توجد معلومات حساسة في رسائل الخطأ

// // ملاحظة: SY4-settings لا يتعامل مع:
// // - رفع ملفات (لا حاجة لـ file upload security)
// // - CORS (API داخلية)
// // - CSRF (API stateless)
// // - Session management (JWT)
```

## 10. توصيات إضافية (Additional Recommendations)

```php
// // 1. استخدام HTTPS فقط
// // 2. تغيير مفتاح تشفير التطبيق (APP_KEY) بانتظام
// // 3. مراجعة سجلات التدقيق أسبوعياً
// // 4. إشعار المسؤولين عند تغيير إعدادات الأمان
// // 5. عمل نسخ احتياطي لجدول system_settings يومياً
// // 6. استخدام IP whitelist للوصول إلى API الإعدادات

// // 7. في المستقبل: إضافة Two-Factor Authentication
// //    لتأكيد التغييرات الحساسة (مثل إعدادات SMTP)
// // 8. في المستقبل: استخدام Approval workflow
// //    يتطلب موافقة مسؤول آخر على التغييرات الكبيرة
```
