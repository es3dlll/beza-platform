# 13 - معالجة الاستثناءات (Exception Handling) - لوحة تحكم الوكيل

## استثناءات لوحة التحكم

```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DashboardDataUnavailableException extends Exception
{
    public function __construct(
        private int $agentId,
        private string $dataType = 'statistics',
        string $message = 'بيانات لوحة التحكم غير متاحة حالياً. يرجى المحاولة لاحقاً.',
        int $code = 503,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        Log::error('بيانات لوحة التحكم غير متاحة', [
            'agent_id' => $this->agentId,
            'data_type' => $this->dataType,
            'exception' => $this->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'DASHBOARD_DATA_UNAVAILABLE',
            'data_type' => $this->dataType,
            'retry_after' => 30, // اقتراح إعادة المحاولة بعد 30 ثانية
        ], 503);
    }
}

class InvalidDateRangeException extends Exception
{
    public function __construct(
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
        string $message = 'نطاق التاريخ غير صحيح',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        Log::warning('نطاق تاريخ غير صحيح في لوحة التحكم', [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ]);

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'INVALID_DATE_RANGE',
            'errors' => [
                'date_range' => $this->getArabicMessage(),
            ],
        ], 422);
    }

    private function getArabicMessage(): string
    {
        if ($this->dateFrom && $this->dateTo && $this->dateFrom > $this->dateTo) {
            return 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية.';
        }

        if ($this->dateFrom && $this->dateFrom > now()->toDateString()) {
            return 'تاريخ البداية لا يمكن أن يكون في المستقبل.';
        }

        if ($this->dateTo && $this->dateTo > now()->toDateString()) {
            return 'تاريخ النهاية لا يمكن أن يكون في المستقبل.';
        }

        return 'نطاق التاريخ المحدد غير صحيح. يرجى التحقق من التواريخ وإعادة المحاولة.';
    }
}

class AgentNotActiveException extends Exception
{
    public function __construct(
        private int $agentId,
        private string $currentStatus,
        string $message = 'حساب الوكيل غير نشط',
        int $code = 403,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        Log::warning('محاولة وصول وكيل غير نشط للوحة التحكم', [
            'agent_id' => $this->agentId,
            'status' => $this->currentStatus,
        ]);

        $statusMessages = [
            'pending' => 'حسابك قيد المراجعة. يرجى انتظار الموافقة.',
            'suspended' => 'تم تعليق حسابك. يرجى التواصل مع الدعم.',
            'rejected' => 'لم تتم الموافقة على طلب تسجيلك. يرجى التواصل مع الدعم.',
        ];

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'AGENT_NOT_ACTIVE',
            'status' => $this->currentStatus,
            'details' => $statusMessages[$this->currentStatus] ?? 'الحساب غير نشط.',
        ], 403);
    }
}

class ExcessiveDataRequestException extends Exception
{
    public function __construct(
        private int $requestedDays,
        private int $maxAllowedDays = 365,
        string $message = 'نطاق البيانات المطلوب كبير جداً',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'EXCESSIVE_DATA_REQUEST',
            'errors' => [
                'date_range' => "الحد الأقصى المسموح به هو {$this->maxAllowedDays} يوماً. لقد طلبت {$this->requestedDays} يوماً.",
            ],
        ], 422);
    }
}

class CacheMaintenanceException extends Exception
{
    public function __construct(
        string $message = 'خطأ في نظام التخزين المؤقت',
        int $code = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        Log::error('خطأ في نظام التخزين المؤقت للوحة التحكم', [
            'exception' => $this->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ مؤقت في النظام. يرجى المحاولة لاحقاً.',
            'code' => 'CACHE_MAINTENANCE',
        ], 500);
    }
}
```

## معالج الاستثناءات العام (Handler)

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardHandler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (DashboardDataUnavailableException $e) {
            return $e->render();
        });

        $this->renderable(function (InvalidDateRangeException $e) {
            return $e->render();
        });

        $this->renderable(function (AgentNotActiveException $e) {
            return $e->render();
        });

        $this->renderable(function (ExcessiveDataRequestException $e) {
            return $e->render();
        });

        $this->renderable(function (CacheMaintenanceException $e) {
            return $e->render();
        });
    }
}
```

## قائمة الاستثناءات

| الاستثناء | كود HTTP | كود الخطأ | الوصف |
|-----------|----------|-----------|-------|
| DashboardDataUnavailableException | 503 | DASHBOARD_DATA_UNAVAILABLE | بيانات لوحة التحكم غير متاحة (مشكلة في المصدر) |
| InvalidDateRangeException | 422 | INVALID_DATE_RANGE | نطاق التواريخ غير صحيح |
| AgentNotActiveException | 403 | AGENT_NOT_ACTIVE | الوكيل غير نشط (معلق/موقوف) |
| ExcessiveDataRequestException | 422 | EXCESSIVE_DATA_REQUEST | طلب كمية بيانات كبيرة جداً |
| CacheMaintenanceException | 500 | CACHE_MAINTENANCE | خطأ في نظام التخزين المؤقت |
