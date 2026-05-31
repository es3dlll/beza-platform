# 13 - معالجة الاستثناءات: أخطاء الإعدادات، فشل الكاش، المفاتيح غير المعروفة (Exception Handling)

## نظرة عامة (Overview)

نظام إعدادات النظام يجب أن يكون موثوقاً حتى في حالات الفشل. نتعامل مع ثلاثة أنواع رئيسية من الأخطاء: الإعدادات غير المعروفة، فشل التخزين المؤقت، وفشل قاعدة البيانات.

## استثناءات مخصصة (Custom Exceptions)

```php
<?php
// // ملف: app/Services/Settings/Exceptions/SettingUpdateException.php
// // استثناء يرمى عند فشل تحديث الإعدادات

namespace App\Services\Settings\Exceptions;

use Exception;

class SettingUpdateException extends Exception
{
    /**
     * // إنشاء استثناء تحديث الإعدادات
     * 
     * // @param string     $message  رسالة الخطأ
     * // @param int        $code     كود الخطأ
     * // @param \Throwable $previous الخطأ الأصلي
     */
    public function __construct(
        string $message = 'فشل تحديث إعدادات النظام',
        int $code = 500,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * // تقرير الاستثناء (تسجيل في Log)
     */
    public function report(): void
    {
        \Illuminate\Support\Facades\Log::error('SY4: ' . $this->getMessage(), [
            'code'  => $this->getCode(),
            'trace' => $this->getTraceAsString(),
        ]);
    }
}

// // ملف: app/Services/Settings/Exceptions/SettingNotFoundException.php
// // استثناء يرمى عند طلب إعداد غير موجود

namespace App\Services\Settings\Exceptions;

use Exception;

class SettingNotFoundException extends Exception
{
    public function __construct(
        string $key,
        ?\Throwable $previous = null
    ) {
        $message = "الإعداد '{$key}' غير موجود في النظام";
        parent::__construct($message, 404, $previous);
    }

    public function report(): void
    {
        \Illuminate\Support\Facades\Log::warning('SY4: ' . $this->getMessage());
    }
}

// // ملف: app/Services/Settings/Exceptions/CacheFailureException.php
// // استثناء يرمى عند فشل التخزين المؤقت (غير حرج)

namespace App\Services\Settings\Exceptions;

use Exception;

class CacheFailureException extends Exception
{
    /**
     * // فشل الكاش ليس خطأ حرجاً
     * // النظام يستمر في العمل ويقرأ من DB
     */
    public function __construct(
        string $message = 'فشل الاتصال بخادم التخزين المؤقت',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 500, $previous);
    }

    /**
     * // لا نوقف التطبيق بسبب فشل الكاش
     * // فقط نسجل التحذير ونكمل
     */
    public function render(): bool
    {
        \Illuminate\Support\Facades\Log::warning('SY4: ' . $this->getMessage());
        return false; // لا نعرض الخطأ للمستخدم
    }
}
```

## معالج الاستثناءات العام (Global Handler)

```php
<?php
// // ملف: app/Exceptions/Handler.php
// // إضافة معالجة خاصة لاستثناءات SY4

namespace App\Exceptions;

use App\Services\Settings\Exceptions\SettingNotFoundException;
use App\Services\Settings\Exceptions\SettingUpdateException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * // تسجيل الاستثناءات
     */
    public function register(): void
    {
        // // معالجة استثناءات SY4-settings
        $this->reportable(function (SettingUpdateException $e) {
            // // مسجل تلقائياً في class نفسه
            return false;
        });

        $this->reportable(function (SettingNotFoundException $e) {
            // // فقط تحذير، ليس خطأ
            return false;
        });
    }

    /**
     * // تحويل الاستثناءات إلى استجابات JSON
     */
    public function render($request, Throwable $e)
    {
        // // إذا كان الطلب لواجهة API
        if ($request->expectsJson() || $request->is('api/*')) {

            // // إعداد غير موجود -> 404
            if ($e instanceof SettingNotFoundException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            }

            // // فشل التحديث -> 500
            if ($e instanceof SettingUpdateException) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث الإعدادات',
                ], 500);
            }

            // // خطأ تحقق -> 422
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صالحة',
                    'errors'  => $e->errors(),
                ], 422);
            }

            // // أي خطأ آخر -> 500
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ داخلي في الخادم',
            ], 500);
        }

        return parent::render($request, $e);
    }
}
```

## أمثلة على معالجة الأخطاء في الكونترولر

```php
<?php
// // مقتطفات من SystemSettingsController مع معالجة الأخطاء

public function index(): JsonResponse
{
    try {
        $settings = $this->settingsService->getAll();

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    } catch (\Throwable $e) {
        // // خطأ غير متوقع: تسجيل وإرجاع رسالة عامة
        Log::error('SY4: فشل جلب جميع الإعدادات', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'تعذر جلب إعدادات النظام حالياً',
        ], 500);
    }
}

public function update(string $group, Request $request): JsonResponse
{
    try {
        // // 1. التحقق من صحة المجموعة
        if (!in_array($group, $this->validator->getAllowedGroups())) {
            throw new \InvalidArgumentException(
                "المجموعة '{$group}' غير معروفة"
            );
        }

        // // 2. التحقق من صحة البيانات
        $validated = $this->validator->validate($group, $request->all());

        // // 3. تحديث الإعدادات
        $this->settingsService->setGroup($group, $validated);

        return response()->json([
            'success' => true,
            'message' => "تم تحديث إعدادات {$group} بنجاح",
        ]);

    } catch (ValidationException $e) {
        // // خطأ تحقق - 422
        return response()->json([
            'success' => false,
            'message' => 'بيانات غير صالحة',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\InvalidArgumentException $e) {
        // // مجموعة غير معروفة - 400
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);

    } catch (SettingUpdateException $e) {
        // // فشل تحديث DB - 500
        return response()->json([
            'success' => false,
            'message' => 'فشل تحديث الإعدادات. الرجاء المحاولة مرة أخرى',
        ], 500);

    } catch (\Throwable $e) {
        // // خطأ غير متوقع - 500
        Log::error('SY4: خطأ غير متوقع في تحديث الإعدادات', [
            'group' => $group,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ غير متوقع',
        ], 500);
    }
}
```

## سيناريوهات الفشل (Failure Scenarios)

```php
// // السيناريو 1: Redis غير متصل
// // -> SettingsCacheManager يمسك الاستثناء
// -> يسجل تحذير في Log
// -> يرجع null
// -> SettingsService يقرأ من MySQL مباشرة
// -> المنصة تعمل بشكل طبيعي (بدون كاش)

// // السيناريو 2: مفتاح إعداد غير معروف
// -> SettingValidator يرمي InvalidArgumentException
// -> الكونترولر يرجع 400 Bad Request
// -> العميل يعرف أن المفتاح خطأ

// // السيناريو 3: فشل transaction في قاعدة البيانات
// -> DB::rollBack() يتراجع عن كل التغييرات
// -> SettingUpdateException يرمى
// -> الكونترولر يرجع 500
// -> يتم تسجيل الخطأ كاملاً في Log

// // السيناريو 4: قيمة غير صالحة (مثل: نص في حقل رقمي)
// -> Validator يرمي ValidationException
// -> الكونترولر يرجع 422 مع تفاصيل الأخطاء
// -> العميل يصحح البيانات ويعيد المحاولة
```
