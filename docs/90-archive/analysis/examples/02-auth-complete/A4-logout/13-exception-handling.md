# 13 - معالجة الاستثناءات (Exception Handling)

## لا توجد استثناءات خاصة

عملية تسجيل الخروج لا ترمي استثناءات مخصصة.

## الحالات المحتملة

| الحالة | كود HTTP | الرسالة |
|--------|----------|---------|
| نجاح | 200 | تم تسجيل الخروج بنجاح |
| بدون توكن (غير مصادق) | 401 | Unauthenticated |
| توكن غير صالح | 401 | Unauthenticated |

## معالجة في Handler

```php
<?php
// app/Exceptions/Handler.php

// الحالة الوحيدة المحتملة: التوكن غير صالح
// وهذا نادر لأن auth:api يضمن وجوده

// ولكن إذا حدث:
public function render($request, Throwable $e)
{
    if ($request->is('api/*') && $e instanceof \RuntimeException) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تسجيل الخروج',
        ], 500);
    }

    return parent::render($request, $e);
}
```
