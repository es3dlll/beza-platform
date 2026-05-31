# 13 - كل الاستثناءات ومعالجتها (Exception Handling)

## IncompleteStatsException

```php
<?php
// app/Exceptions/Admin/IncompleteStatsException.php

namespace App\Exceptions\Admin;

use Exception;

class IncompleteStatsException extends Exception
{
    public function __construct(string $missingField)
    {
        parent::__construct("بيانات لوحة التحكم غير مكتملة: {$missingField}");
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'تعذر تحميل إحصائيات لوحة التحكم',
            'errors'  => [
                'stats' => ['بعض البيانات غير متوفرة حالياً. حاول مرة أخرى'],
            ],
        ], 500);
    }
}
```

## UnauthorizedAdminException

```php
<?php
// app/Exceptions/Admin/UnauthorizedAdminException.php

namespace App\Exceptions\Admin;

use Exception;

class UnauthorizedAdminException extends Exception
{
    public function __construct()
    {
        parent::__construct('صلاحيات المشرف مطلوبة');
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'غير مصرح بالدخول',
            'errors'  => [
                'admin' => ['هذه الصفحة تتطلب صلاحيات المشرف'],
            ],
        ], 403);
    }
}
```

## CacheFailureException

```php
<?php
// app/Exceptions/Admin/CacheFailureException.php

namespace App\Exceptions\Admin;

use Exception;

class CacheFailureException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        // لا نوقف الطلب — نرجع البيانات من DB مباشرة
        return response()->json([
            'success' => false,
            'message' => 'خدمة التخزين المؤقت غير متوفرة',
        ], 200); // نمرر مع تنبيه
    }
}
```

## معالجة في Handler

```php
<?php
// app/Exceptions/Handler.php

public function render($request, Throwable $e)
{
    if ($request->is('api/admin/*')) {
        if ($e instanceof \App\Exceptions\Admin\IncompleteStatsException) {
            return $e->render();
        }
    }

    return parent::render($request, $e);
}
```

## جدول رموز الأخطاء

| كود HTTP | الاستثناء | الرسالة |
|----------|-----------|---------|
| 403 | `UnauthorizedAdminException` | غير مصرح بالدخول |
| 500 | `IncompleteStatsException` | بيانات غير مكتملة |
| 200 | `CacheFailureException` | خدمة التخزين المؤقت غير متوفرة |
| 500 | خطأ DB | فشل الاتصال بقاعدة البيانات |
