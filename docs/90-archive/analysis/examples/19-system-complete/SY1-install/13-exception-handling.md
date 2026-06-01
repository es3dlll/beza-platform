# 13 - معالجة الاستثناءات (Exception Handling)

## InstallerException الأساسي

```php
<?php
// app/Exceptions/Install/InstallerException.php

namespace App\Exceptions\Install;

use Exception;
use Illuminate\Http\JsonResponse;

class InstallerException extends Exception
{
    public function __construct(
        string $message = 'حدث خطأ أثناء التنصيب',
        protected readonly int $httpStatusCode = 500,
        protected readonly ?string $step = null,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data'    => [
                'step'  => $this->step,
                'error' => $this->getMessage(),
            ],
        ], $this->httpStatusCode);
    }
}
```

## استثناءات متخصصة

```php
<?php
// app/Exceptions/Install/DatabaseConnectionException.php

namespace App\Exceptions\Install;

class DatabaseConnectionException extends InstallerException
{
    public function __construct(string $message = 'فشل الاتصال بقاعدة البيانات')
    {
        parent::__construct(
            message: $message,
            httpStatusCode: 422,
            step: 'database',
        );
    }
}
```

```php
<?php
// app/Exceptions/Install/EnvironmentWriteException.php

namespace App\Exceptions\Install;

class EnvironmentWriteException extends InstallerException
{
    public function __construct(string $message = 'فشل كتابة ملف البيئة (.env)')
    {
        parent::__construct(
            message: $message,
            httpStatusCode: 500,
            step: 'environment',
        );
    }
}
```

```php
<?php
// app/Exceptions/Install/MigrationFailedException.php

namespace App\Exceptions\Install;

class MigrationFailedException extends InstallerException
{
    public function __construct(string $message = 'فشل تشغيل الترحيلات')
    {
        parent::__construct(
            message: $message,
            httpStatusCode: 500,
            step: 'migration',
        );
    }
}
```

```php
<?php
// app/Exceptions/Install/AdminCreationException.php

namespace App\Exceptions\Install;

class AdminCreationException extends InstallerException
{
    public function __construct(string $message = 'فشل إنشاء المشرف')
    {
        parent::__construct(
            message: $message,
            httpStatusCode: 500,
            step: 'admin',
        );
    }
}
```

## معالج الاستثناءات — Handler

```php
<?php
// app/Exceptions/Handler.php — إضافة

namespace App\Exceptions;

use App\Exceptions\Install\DatabaseConnectionException;
use App\Exceptions\Install\EnvironmentWriteException;
use App\Exceptions\Install\InstallerException;
use App\Exceptions\Install\MigrationFailedException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        // تسجيل جميع استثناءات المثبت
        $this->reportable(function (InstallerException $e) {
            Log::error('خطأ في مثبت Beza', [
                'step'  => $e->step,
                'error' => $e->getMessage(),
            ]);
        });

        $this->renderable(function (InstallerException $e) {
            return $e->render();
        });
    }
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة | الخطوة |
|----------|-----------|---------|--------|
| 403 | `InstallerLockedException` | تم إكمال التنصيب مسبقاً | — |
| 422 | `DatabaseConnectionException` | فشل الاتصال بقاعدة البيانات | database |
| 422 | `ValidationException` | بيانات غير صحيحة | أي خطوة |
| 500 | `EnvironmentWriteException` | فشل كتابة ملف البيئة | environment |
| 500 | `MigrationFailedException` | فشل تشغيل الترحيلات | migration |
| 500 | `AdminCreationException` | فشل إنشاء المشرف | admin |
| 500 | `InstallerException` | حدث خطأ أثناء التنصيب | عام |

## استراتيجية التعامل مع الفشل

```
خطوة 2: فشل اتصال MySQL
   ↓
   → عرض رسالة الخطأ للمستخدم (خطأ في بيانات الدخول)
   → السماح بإعادة المحاولة (لا حاجة لتراجع — لم يتغير شيء)

خطوة 3: فشل كتابة .env
   ↓
   → عرض رسالة (صلاحيات الملف أو القرص ممتلئ)
   → السماح بإعادة المحاولة بعد تصحيح المشكلة

خطوة 4: فشل الترحيلات
   ↓
   → لا يمكن التراجع عن .env (قد يكون مكتوباً جزئياً)
   → عرض رسالة للمستخدم لحذف .env يدوياً وإعادة المحاولة
   → تسجيل الخطأ في السجل

خطوة 5: فشل إنشاء المشرف
   ↓
   → الترحيلات تمت بنجاح (الجداول موجودة)
   → السماح بإعادة محاولة إنشاء المشرف فقط (دون إعادة الترحيلات)

خطوة 6: فشل تعطيل المثبت
   ↓
   → كل شيء تم بنجاح عدا تعطيل المثبت
   → تعطيل المثبت يدوياً عبر CLI: php artisan install:lock
```

## إعادة المحاولة الآمنة

بما أن المثبت لا يستخدم `DB::transaction` (كل خطوة مستقلة)، فإن إعادة المحاولة آمنة:

```php
// المثبت يتحقق من كل خطوة قبل تنفيذها
// إذا كانت الخطوة 4 (ميجريشن) قد فشلت، يمكن إعادة تشغيلها
// لأن الميجريشن يستخدم أسماء الجداول ويكتشف أنها موجودة

// مثال: إعادة تشغيل الميجريشن
try {
    Artisan::call('migrate', ['--force' => true]);
} catch (\Throwable $e) {
    throw new MigrationFailedException($e->getMessage());
}
```
