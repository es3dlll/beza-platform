# 13 - الاستثناءات (Exception Handling)

```php
<?php
// app/Exceptions/Admin/InvalidSettingKeyException.php
class InvalidSettingKeyException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'مفتاح الإعداد غير صحيح',
        ], 422);
    }
}

// app/Exceptions/Admin/MaintenanceModeActiveException.php
class MaintenanceModeActiveException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'المنصة في وضع الصيانة حالياً. بعض الخدمات غير متوفرة.',
            'data'    => [
                'retry_after' => 300, // 5 دقائق
            ],
        ], 503);
    }
}
```

## Middleware للتحقق من وضع الصيانة

```php
<?php
// app/Http/Middleware/CheckMaintenanceMode.php

namespace App\Http\Middleware;

use App\Exceptions\Admin\MaintenanceModeActiveException;
use App\Services\Admin\ConfigCacheService;
use Closure;

class CheckMaintenanceMode
{
    public function __construct(
        private readonly ConfigCacheService $configCache
    ) {}

    public function handle($request, Closure $next)
    {
        if ($this->configCache->isMaintenanceMode()) {
            // استثناء المشرفين
            if ($request->user() && $request->user()->is_admin) {
                return $next($request);
            }

            throw new MaintenanceModeActiveException();
        }

        return $next($request);
    }
}
```

## جدول الأخطاء

| كود | الاستثناء | الرسالة |
|-----|-----------|---------|
| 422 | InvalidSettingKeyException | مفتاح الإعداد غير صحيح |
| 503 | MaintenanceModeActiveException | المنصة في وضع الصيانة |
| 403 | UnauthorizedAdminException | صلاحية مشرف مطلوبة |
