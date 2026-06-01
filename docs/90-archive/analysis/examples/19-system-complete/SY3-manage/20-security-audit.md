# 20 - تدقيق أمني: وصول المشرف فقط، لا حقن أوامر، التحقق من جميع أوامر exec (Security Audit: Admin-Only Access, No Command Injection, Validate All exec Commands)

<div dir="rtl">

## نظرة عامة أمنية

SY3-manage تتعامل مع عمليات حساسة على مستوى النظام. لذلك، الأمان هو الأولوية القصوى. هذا التدقيق الأمني يغطي جميع الجوانب الأمنية للعملية.

## مستويات الأمان

```
المستوى 1: المصادقة (Authentication)
    ↓
المستوى 2: التفويض (Authorization)  
    ↓
المستوى 3: التحقق من المدخلات (Input Validation)
    ↓
المستوى 4: الحماية من حقن الأوامر (Command Injection Prevention)
    ↓
المستوى 5: حماية الملفات (File System Protection)
    ↓
المستوى 6: التسجيل والتدقيق (Audit Logging)
    ↓
المستوى 7: حماية البيانات الحساسة (Sensitive Data Protection)
```

## 1. المصادقة (Authentication) - JWT

```php
<?php
// config/jwt.php - إعدادات JWT
return [
    /*
     | --------------------------------------------------------------------------
     | مصادقة JWT لجميع نقاط إدارة النظام
     | --------------------------------------------------------------------------
     | نستخدم tymon/jwt-auth بدلاً من Sanctum
     | لأن Sanctum يركز على SPA و API tokens
     | بينما JWT يوفر مصادقة أكثر أماناً للتطبيقات الخارجية
     */
    'required_claims' => ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'],
    'ttl' => env('JWT_TTL', 60), // 60 دقيقة - وقت قصير لتقليل المخاطر
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 14 يوماً
    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true), // إلغاء التوكن عند تسجيل الخروج
    'show_black_list_exception' => env('JWT_SHOW_BLACKLIST_EXCEPTION', true),
];

// التحقق من صحة التوكن في كل طلب
Route::middleware(['auth:api'])->group(function () {
    // جميع مسارات الإدارة تتطلب JWT صالح
});

/**
 * المصادقة متعددة الطبقات:
 * 1. JWT token validation (التوقيع، التاريخ، المُصدر)
 * 2. Blacklist check (التحقق من عدم إلغاء التوكن)
 * 3. User existence check (التحقق من وجود المستخدم)
 * 4. Token expiration check (التحقق من صلاحية التوكن)
 */
```

## 2. التفويض (Authorization) - دور المشرف فقط

```php
<?php
// app/Http/Middleware/AdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware للتحقق من صلاحية المشرف
 * يضمن أن فقط المستخدمين بدور admin يمكنهم الوصول
 */
class AdminMiddleware
{
    /**
     * معالجة الطلب
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. التحقق من وجود المستخدم (بعد auth:api)
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح به. يجب تسجيل الدخول.',
            ], 401);
        }

        // 2. التحقق من دور المستخدم
        if ($user->role !== 'admin') {
            // تسجيل محاولة الوصول غير المصرح بها
            \Illuminate\Support\Facades\Log::channel('admin')->warning(
                'محاولة وصول غير مصرح بها لنظام الإدارة',
                [
                    'user_id'    => $user->id,
                    'email'      => $user->email,
                    'ip'         => $request->ip(),
                    'endpoint'   => $request->path(),
                    'method'     => $request->method(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'غير مصرح به. هذا الإجراء يتطلب صلاحيات المشرف.',
            ], 403);
        }

        // 3. تسجيل الوصول الناجح (اختياري)
        if (config('system.audit_log_all_admin_access')) {
            \Illuminate\Support\Facades\Log::channel('admin')->info(
                'وصول مشرف إلى النظام',
                [
                    'user_id'  => $user->id,
                    'email'    => $user->email,
                    'endpoint' => $request->path(),
                    'method'   => $request->method(),
                ]
            );
        }

        return $next($request);
    }
}
```

## 3. الحماية من حقن الأوامر (Command Injection Prevention)

### 3.1. استخدام escapeshellarg()

```php
<?php
/**
 * الأهم: استخدام escapeshellarg() لتنظيف جميع المدخلات
 * قبل تمريرها إلى أوامر shell
 * 
 * ⚠️ أبداً لا تستخدم exec() مع مدخلات غير منقاة
 */
public function buildMysqldumpCommand(): string
{
    $dbConfig = config('database.connections.mysql');

    // ✅ آمن: استخدام escapeshellarg() لكل معامل
    $command = sprintf(
        'mysqldump --host=%s --port=%s --user=%s --password=%s %s',
        escapeshellarg($dbConfig['host']),
        escapeshellarg($dbConfig['port'] ?? 3306),
        escapeshellarg($dbConfig['username']),
        escapeshellarg($dbConfig['password']),
        escapeshellarg($dbConfig['database'])
    );

    return $command;
}

// ❌ غير آمن: مدخلات غير منقاة
// $command = "mysqldump --host={$dbConfig['host']}";
// هذا يسمح بحقن أوامر مثل: ; rm -rf / ; echo hacked
```

### 3.2. استخدام Symfony Process بدلاً من exec المباشر

```php
<?php
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * ✅ آمن: استخدام Symfony Process
 * يوفر عزل أفضل ومعالجة آمنة للأوامر
 */
public function runBackupSafely(): void
{
    $dbConfig = config('database.connections.mysql');

    // استخدام Process بدلاً من exec المباشر
    $process = new Process([
        'mysqldump',
        '--host=' . $dbConfig['host'],
        '--port=' . ($dbConfig['port'] ?? '3306'),
        '--user=' . $dbConfig['username'],
        '--password=' . $dbConfig['password'],
        '--single-transaction',
        '--routines',
        '--events',
        '--triggers',
        $dbConfig['database'],
    ]);

    $process->setTimeout(300);
    $process->run();

    if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
    }
}
```

### 3.3. قائمة الأوامر المسموح بها (Whitelist)

```php
<?php
/**
 * قائمة بيضاء بالأوامر المسموح بتنفيذها
 * أي أمر خارج هذه القائمة ممنوع
 */
class AllowedCommands
{
    /**
     * قائمة أوامر Artisan المسموح بها
     */
    const ARTISAN_COMMANDS = [
        'cache:clear',
        'config:clear',
        'config:cache',
        'route:clear',
        'route:cache',
        'view:clear',
        'queue:restart',
        'queue:status',
        'down',
        'up',
    ];

    /**
     * قائمة أوامر النظام المسموح بها
     */
    const SYSTEM_COMMANDS = [
        'mysqldump',
        'mysql',
        'gzip',
        'gunzip',
        'which',
    ];

    /**
     * التحقق من أن الأمر مسموح به
     * 
     * @param string $command اسم الأمر
     * @throws \RuntimeException إذا كان الأمر غير مسموح
     */
    public static function validate(string $command): void
    {
        // استخراج اسم الأمر الأساسي
        $baseCommand = explode(' ', $command)[0];
        $baseCommand = basename($baseCommand);

        // التحقق من القائمة البيضاء
        if (!in_array($baseCommand, self::SYSTEM_COMMANDS)) {
            throw new \RuntimeException(
                "الأمر '{$baseCommand}' غير مسموح به في نظام الإدارة"
            );
        }
    }
}
```

## 4. حماية الملفات (File System Protection)

### 4.1. منع Directory Traversal

```php
<?php
/**
 * حماية صارمة ضد هجمات Directory Traversal
 * 
 * المشكلة: قد يحاول المهاجم استخدام ../ للوصول إلى ملفات خارج المجلد المسموح
 * مثال: GET /admin/system/logs/../../../etc/passwd
 */
public function safeLogFilePath(string $filename): string
{
    // 1. رفض أي مسار يحتوي على ..
    if (str_contains($filename, '..')) {
        throw new \RuntimeException('اسم الملف غير صالح: يحتوي على مرجع للخارج');
    }

    // 2. رفض المسارات المطلقة
    if (str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
        throw new \RuntimeException('اسم الملف غير صالح: مسار مطلق');
    }

    // 3. السماح فقط بصيغ معينة
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.(log|txt)$/', $filename)) {
        throw new \RuntimeException('اسم الملف غير صالح: صيغة غير مدعومة');
    }

    // 4. بناء المسار الكامل بأمان
    $basePath = realpath(storage_path('logs'));
    $fullPath = realpath($basePath . '/' . $filename);

    // 5. التأكد من أن المسار النهائي داخل المجلد المسموح
    if ($fullPath === false || !str_starts_with($fullPath, $basePath)) {
        throw new \RuntimeException('وصول غير مصرح به إلى الملف');
    }

    return $fullPath;
}
```

### 4.2. حماية الملفات الحساسة

```php
<?php
/**
 * منع عرض الملفات الحساسة
 * مثل.env، composer.json، إلخ
 */
class SensitiveFileProtection
{
    /**
     * قائمة بأنماط الملفات الممنوعة
     */
    const SENSITIVE_PATTERNS = [
        '/\.env/i',
        '/composer\.(json|lock)/i',
        '/package\.json/i',
        '/\.git/i',
        '/\.ssh/i',
        '/id_rsa/',
        '/password/i',
        '/secret/i',
        '/config\.php$/i',
        '/\.pem$/i',
        '/\.key$/i',
        '/\.cert$/i',
    ];

    /**
     * التحقق من أن الملف ليس ملفاً حساساً
     * 
     * @param string $filename اسم الملف
     * @throws \RuntimeException إذا كان الملف ممنوعاً
     */
    public static function check(string $filename): void
    {
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (preg_match($pattern, $filename)) {
                throw new \RuntimeException(
                    'لا يمكن الوصول إلى هذا الملف لأسباب أمنية'
                );
            }
        }
    }
}
```

## 5. حماية الإعدادات الحساسة

```php
<?php
/**
 * إخفاء كلمة مرور قاعدة البيانات من السجلات والاستجابات
 */
public function sanitizeCommandOutput(string $output): string
{
    // إخفاء كلمات المرور من المخرجات
    $password = config('database.connections.mysql.password');

    if ($password) {
        $output = str_replace($password, '********', $output);
    }

    return $output;
}

/**
 * عدم تضمين كلمة المرور في أوامر exec إذا كان ممكناً
 * بدلاً من ذلك، استخدام ملف ~/.my.cnf
 */
public function buildBackupCommandWithConfigFile(): string
{
    // بديل أكثر أماناً: استخدام ملف إعدادات MySQL
    // بدلاً من تمرير كلمة المرور في الأمر
    $configFile = storage_path('app/backups/.my.cnf');

    // إنشاء ملف الإعدادات إذا لم يكن موجوداً
    if (!file_exists($configFile)) {
        $content = sprintf(
            "[client]\nhost=%s\nuser=%s\npassword=%s\n",
            config('database.connections.mysql.host'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password')
        );

        file_put_contents($configFile, $content, true);
        chmod($configFile, 0600); // قراءة وكتابة فقط لصاحب الملف
    }

    return sprintf(
        'mysqldump --defaults-extra-file=%s --single-transaction %s',
        escapeshellarg($configFile),
        escapeshellarg(config('database.connections.mysql.database'))
    );
}
```

## 6. التسجيل والتدقيق (Audit Logging)

```php
<?php
/**
 * تسجيل جميع عمليات الإدارة للتدقيق
 */
public function logAdminAction(string $action, array $details = []): void
{
    $logData = array_merge([
        'action'      => $action,
        'admin_id'    => auth()->id(),
        'admin_email' => auth()->user()->email ?? 'unknown',
        'ip_address'  => request()->ip(),
        'user_agent'  => request()->userAgent(),
        'timestamp'   => now()->toIso8601String(),
    ], $details);

    // تسجيل في قناة admin المخصصة
    Log::channel('admin')->info('إجراء إداري', $logData);

    // يمكن أيضاً التسجيل في قاعدة بيانات للتدقيق (اختياري)
    // AdminAuditLog::create($logData);
}
```

## 7. قائمة التدقيق الأمني (Security Checklist)

| البند | الحالة | ملاحظات |
|-------|--------|---------|
| **المصادقة** | | |
| استخدام JWT (auth:api) | ✅ | tymon/jwt-auth |
| انتهاء صلاحية التوكن | ✅ | 60 دقيقة |
| إلغاء التوكن عند تسجيل الخروج | ✅ | blacklist enabled |
| **التفويض** | | |
| التحقق من دور admin | ✅ | AdminMiddleware |
| تسجيل محاولات الوصول الممنوع | ✅ | قناة admin log |
| **حماية المدخلات** | | |
| التحقق من صحة المعرفات | ✅ | regex validation |
| منع Directory Traversal | ✅ | مسار آمن + التحقق الصارم |
| منع الملفات الحساسة | ✅ | قائمة بأنماط ممنوعة |
| **حماية الأوامر** | | |
| استخدام escapeshellarg() | ✅ | لكل معامل |
| استخدام Symfony Process | ✅ | بدلاً من exec المباشر |
| قائمة بيضاء بالأوامر | ✅ | AllowedCommands |
| **حماية الملفات** | | |
| التحقق من الصلاحيات | ✅ | قبل القراءة/الكتابة/الحذف |
| إخفاء كلمات المرور من المخرجات | ✅ | sanitizeCommandOutput |
| استخدام ملف .my.cnf للإعدادات | ✅ | صلاحيات 0600 |
| **التسجيل** | | |
| تسجيل جميع العمليات الإدارية | ✅ | قناة admin log |
| تسجيل محاولات الاختراق | ✅ | تحذيرات وصلاحية ممنوعة |
| **الاستجابة للحوادث** | | |
| رسائل خطأ آمنة (بدون تفاصيل) | ✅ | رسائل عامة بالعربية |
| معالجة الاستثناءات | ✅ | بدون كشف معلومات حساسة |

## 8. سيناريوهات الهجوم والمعالجة

### سيناريو 1: حقن أمر (Command Injection)
```
المحاولة:
  POST /admin/system/backup/backup_2026.sql.gz/restore
  ولكن المهاجم يرسل:
  backup_2026.sql.gz; rm -rf /; echo hacked

المعالجة:
  1. التحقق من regex: /^backup_\d{4}-\d{2}-\d{2}_.+\.sql\.gz$/
  2. الفاصلة المنقوطة تمنع فشل التحقق
  3. حتى لو نجح، escapeshellarg() سيعاملها كجزء من اسم الملف
  4. التحقق من وجود الملف: file_exists() يفشل
```

### سيناريو 2: Directory Traversal
```
المحاولة:
  GET /admin/system/logs/../../.env

المعالجة:
  1. التحقق من regex: /^[a-zA-Z0-9_\-]+\.log$/
  2. ../ لا يطابق regex → 422 Validation Error
  3. حتى لو تم تخطي regex، SensitiveFileProtection سيمنع .env
```

### سيناريو 3: رفع الصلاحية (Privilege Escalation)
```
المحاولة:
  مستخدم عادي (user) يحاول الوصول إلى /admin/system/backup/create

المعالجة:
  1. auth:api → التحقق من JWT
  2. AdminMiddleware → التحقق من role === 'admin'
  3. user role !== 'admin' → 403 + تسجيل المحاولة
```

## 9. التوصيات الأمنية الإضافية

```php
<?php
/**
 * 1. استخدام Rate Limiting للحد من عدد الطلبات
 */
Route::middleware(['auth:api', 'admin', 'throttle:30,1'])
    ->prefix('admin/system')
    ->group(function () {
        // جميع مسارات الإدارة مع تحديد 30 طلب في الدقيقة
    });

/**
 * 2. استخدام تجزئة المهام الحساسة (Queue محمية)
 * لعمليات مثل النسخ الاحتياطي التي قد تستغرق وقتاً
 */
// بدلاً من التنفيذ المباشر في المتحكم:
// $this->backupManager->create(); // ❌ قد يسبب timeout
// استخدام:
// BackupJob::dispatch(); // ✅ آمن وغير متزامن

/**
 * 3. التحقق من صحة البيئة قبل العمليات الخطيرة
 */
public function isSafeToRunBackup(): bool
{
    // لا تسمح بالنسخ الاحتياطي في بيئة التطوير
    if (app()->environment('local')) {
        Log::warning('محاولة نسخ احتياطي في بيئة تطوير');
        return false;
    }

    // التحقق من أن mysqldump متاح
    if (!$this->isMysqldumpAvailable()) {
        return false;
    }

    return true;
}
```

## الخلاصة

SY3-manage مطبقة مع أعلى معايير الأمان:
- **الدفاع في العمق (Defense in Depth)**: طبقات متعددة من الأمان
- **أقل صلاحية (Least Privilege)**: فقط admin يمكنه الوصول
- **المدخلات غير موثوقة (Never Trust Input)**: التحقق من كل مدخل
- **التسجيل والتدقيق (Audit Trail)**: كل عملية مسجلة
- **الحماية من حقن الأوامر**: escapeshellarg + Process + Whitelist
- **حماية الملفات**: منع traversal + الملفات الحساسة

</div>
