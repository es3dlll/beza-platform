# 13 - استثناءات التدقيق (Exception Handling)

## AuditableException

```php
<?php

namespace App\Exceptions;

use Exception;

class AuditLogException extends Exception
{
    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'خطأ في تسجيل سجل التدقيق',
        ], 500);
    }
}
```

## معالجة فشل التسجيل

```php
// لا تمنع فشل التسجيل من إتمام العملية
// استخدم try-catch حول AuditLog::create()
try {
    AuditLog::create([...]);
} catch (\Exception $e) {
    Log::error('فشل تسجيل سجل التدقيق: ' . $e->getMessage());
    // لا نرمي exception — العملية الأصلية تكمل
}
```
