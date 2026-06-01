# 19 - حالات الحافة (Edge Cases)

## 1. محاولة إعادة التنصيب بعد الإكمال

**المشكلة**: مستخدم يحاول الوصول إلى `/install` بعد أن تم التنصيب مسبقاً.

**الحل**: `InstallerController` يتحقق من `INSTALLER_LOCKED=true` في بداية كل طلب.

```php
// كل دالة في InstallerController تبدأ بهذا الفحص
if (env('INSTALLER_LOCKED') === true) {
    return response()->json([
        'success' => false,
        'message' => 'تم إكمال التنصيب مسبقاً. المثبت معطل.',
    ], 403);
}
```

## 2. فشل الاتصال بقاعدة البيانات

**المشكلة**: إدخال بيانات MySQL خاطئة (مضيف خطأ، منفذ مغلق، كلمة مرور خاطئة).

**الحل**: `try-catch` حول PDO connection + رسالة خطأ واضحة.

```
T1: المستخدم يدخل بيانات MySQL
T2: InstallerController يحاول الاتصال عبر PDO
T3: PDO throws exception (مضيف خطأ)
T4: يتم التقاط الخطأ → عرض رسالة: "تعذر الاتصال بالمضيف 192.168.1.100"
T5: المستخدم يصحح البيانات ويعيد المحاولة
T6: لا تغيير في النظام — لا تراجع مطلوب
```

## 3. فشل إنشاء قاعدة البيانات

**المشكلة**: المستخدم `beza_user` لا يملك صلاحية `CREATE DATABASE`.

**الحل**: محاولة إنشاء DB، وإن فشلت، نطلب من المستخدم إنشاءها يدوياً.

```php
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` ...");
} catch (\PDOException $e) {
    // المستخدم ليس لديه صلاحية CREATE
    // نطلب منه إنشاء قاعدة البيانات يدوياً
    return response()->json([
        'success' => false,
        'message' => 'لا يمكن إنشاء قاعدة البيانات. يرجى إنشاءها يدوياً ثم المحاولة مرة أخرى.',
    ], 422);
}
```

## 4. فشل كتابة .env (القرص ممتلئ)

**المشكلة**: لا توجد مساحة كافية على القرص لكتابة ملف `.env`.

**الحل**: التحقق من `file_put_contents` return value.

```php
$bytesWritten = file_put_contents($this->envPath, $content, LOCK_EX);
if ($bytesWritten === false) {
    throw new \RuntimeException('فشل كتابة ملف .env — تأكد من صلاحية الكتابة ومساحة القرص');
}
```

## 5. انقطاع التيار أثناء كتابة .env

**المشكلة**: انقطاع الكهرباء في منتصف كتابة `.env` — ملف تالف.

**الحل**: استخدام `LOCK_EX` + ملف مؤقت ثم rename.

```php
// كتابة إلى ملف مؤقت أولاً
$tempPath = $this->envPath . '.tmp';
file_put_contents($tempPath, $content, LOCK_EX);

// ثم rename (عملية ذرية)
rename($tempPath, $this->envPath);
```

## 6. فشل `php artisan key:generate`

**المشكلة**: أمر Artisan يفشل لأن `APP_KEY` موجود أو مشاكل أخرى.

**الحل**: استخدام `--force` لتجاوز القيمة الموجودة.

```php
$exitCode = Artisan::call('key:generate', ['--force' => true]);
if ($exitCode !== 0) {
    Log::error('فشل توليد APP_KEY', ['output' => Artisan::output()]);
    throw new \RuntimeException('فشل توليد مفتاح التطبيق');
}
```

## 7. فشل `php artisan migrate` بسبب خطأ في SQL

**المشكلة**: أحد ملفات الميجريشن يحتوي على خطأ (مثل عمود مكرر).

**الحل**: عرض مخرجات Artisan للمستخدم لتشخيص المشكلة.

```php
Artisan::call('migrate', ['--force' => true]);
$output = Artisan::output();

// التحقق من وجود أخطاء
if (str_contains($output, 'Error') || str_contains($output, 'exception')) {
    Log::error('فشل الترحيلات', ['output' => $output]);
    return response()->json([
        'success' => false,
        'message' => 'فشل تشغيل الترحيلات',
        'data'    => ['output' => $output],
    ], 500);
}
```

## 8. إنشاء مشرف بنفس البريد

**المشكلة**: محاولة إنشاء مشرف ببريد إلكتروني موجود (مثلاً من تنصيب سابق).

**الحل**: `Rule::unique('users', 'email')` يمنع التكرار + رسالة واضحة.

## 9. المستخدم يغلق المتصفح في منتصف التنصيب

**المشكلة**: المستخدم يغلق المتصفح بعد كتابة `.env` وقبل الترحيلات.

**الحل**: عند إعادة فتح `/install`، يكتشف المثبت أن `.env` موجود ويعرض استئناف التنصيب.

```php
// في InstallerController@welcome
if (file_exists(base_path('.env')) && env('INSTALLER_LOCKED') !== true) {
    // .env موجود والمثبت غير مقفول → تنصيب غير مكتمل
    return response()->json([
        'success' => true,
        'resumable' => true,
        'message' => 'يوجد تنصيب غير مكتمل. هل تريد الاستئناف؟',
    ]);
}
```

## 10. صلاحيات المجلدات غير كافية

**المشكلة**: `storage/` أو `bootstrap/cache/` غير قابلة للكتابة.

**الحل**: الفحص المسبق يكتشف المشكلة ويعرض تحذيراً.

## 11. Redis غير مثبت أو غير متاح

**المشكلة**: Redis غير مثبت على الخادم لكن المستخدم اختار `queue_connection=redis`.

**الحل**: الفحص المسبق يكتشف Redis CLI — إذا لم يكن موجوداً، نحذر المستخدم.

## 12. إصدار PHP غير متوافق

**المشكلة**: الخادم يعمل على PHP 7.4 ولكن Beza يتطلب 8.1+.

**الحل**: `checkPhpVersion()` يفشل — المثبت لا يسمح بالمتابعة.

```
T1: فحص PHP → PHP 7.4
T2: all_pass = false
T3: زر "التالي" معطل
T4: رسالة: "PHP 7.4 غير متوافق. الرجاء الترقية إلى PHP 8.1 أو أحدث"
```

## جدول ملخص حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة |
|---|--------|---------|---------------|
| 1 | إعادة تنصيب بعد الإكمال | رفض (403) | InstallerController |
| 2 | فشل اتصال MySQL | رسالة خطأ + إعادة محاولة | PDO Exception |
| 3 | إنشاء DB بدون صلاحية | رسالة توجيهية | PDO Exception |
| 4 | قرص ممتلئ | RuntimeException | file_put_contents |
| 5 | انقطاع الكهرباء | ملف مؤقت + rename | ملف مؤقت |
| 6 | فشل key:generate | RuntimeException | Artisan exit code |
| 7 | خطأ في الميجريشن | عرض المخرجات للمستخدم | Artisan output |
| 8 | بريد مكرر | Validation error | Form Request |
| 9 | إغلاق المتصفح | استئناف التنصيب | Session/File check |
| 10 | صلاحيات مجلدات | تحذير + منع المتابعة | RequirementChecker |
| 11 | Redis غير موجود | تحذير | RequirementChecker |
| 12 | PHP 7.4 | منع المتابعة | RequirementChecker |
