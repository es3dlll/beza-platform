# 13 - معالجة الاستثناءات (Exception Handling)

## استثناءات الخريطة

```php
<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * استثناء إحداثيات غير صالحة
 */
class InvalidCoordinatesException extends \InvalidArgumentException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            $message ?: 'الإحداثيات المدخلة غير صالحة.'
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'coordinates' => [$this->getMessage()],
            ],
        ], 422);
    }

    public function getErrorCode(): string
    {
        return 'INVALID_COORDINATES';
    }
}
```

```php
<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * استثناء عدم وجود وكلاء قريبين
 */
class NoAgentsNearbyException extends \RuntimeException
{
    private ?float $suggestedRadius;

    public function __construct(string $message = '', ?float $suggestedRadius = null)
    {
        parent::__construct(
            $message ?: 'لا يوجد وكلاء متاحون ضمن نطاق البحث.'
        );
        $this->suggestedRadius = $suggestedRadius;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => [
                'suggestion' => 'حاول زيادة نصف قطر البحث.',
                'suggested_radius' => $this->suggestedRadius,
                'nearby_cities' => $this->getNearbyCities(),
            ],
        ], 404);
    }

    public function getErrorCode(): string
    {
        return 'NO_AGENTS_NEARBY';
    }

    private function getNearbyCities(): array
    {
        return [
            'damascus' => ['lat' => 33.5138, 'lng' => 36.2765],
            'aleppo' => ['lat' => 36.2021, 'lng' => 37.1343],
            'homs' => ['lat' => 34.7308, 'lng' => 36.7147],
        ];
    }
}
```

```php
<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * استثناء فشل تحديد الموقع الجغرافي
 */
class GeolocationFailedException extends \RuntimeException
{
    private ?string $reason;

    public function __construct(string $message = '', ?string $reason = null)
    {
        parent::__construct(
            $message ?: 'تعذر تحديد الموقع الجغرافي.'
        );
        $this->reason = $reason;
    }

    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'geolocation' => [$this->getMessage()],
            ],
        ];

        if ($this->reason) {
            $response['data'] = [
                'reason' => $this->reason,
                'recommendation' => match ($this->reason) {
                    'permission_denied' => 'يرجى تفعيل صلاحية الوصول إلى الموقع في إعدادات الجهاز.',
                    'position_unavailable' => 'تعذر الحصول على الموقع. تأكد من تفعيل GPS.',
                    'timeout' => 'انتهت مهلة تحديد الموقع. حاول مرة أخرى.',
                    default => 'يرجى التحقق من إعدادات الموقع والمحاولة مرة أخرى.',
                },
            ];
        }

        return response()->json($response, 503);
    }

    public function getErrorCode(): string
    {
        return 'GEOLOCATION_FAILED';
    }
}
```

## تسجيل الاستثناءات في Handler

```php
<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        InvalidCoordinatesException::class,
        NoAgentsNearbyException::class,
    ];

    public function register(): void
    {
        $this->reportable(function (GeolocationFailedException $e) {
            \Illuminate\Support\Facades\Log::warning('فشل تحديد الموقع الجغرافي', [
                'reason' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    }

    public function render($request, Throwable $e): JsonResponse|\Illuminate\Http\Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            if (method_exists($e, 'render')) {
                return $e->render();
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صالحة',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالدخول',
                ], 401);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'الصفحة المطلوبة غير موجودة',
                ], 404);
            }

            if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                return response()->json([
                    'success' => false,
                    'message' => 'طلبات كثيرة جداً. يرجى الانتظار.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ داخلي في الخادم',
            ], 500);
        }

        return parent::render($request, $e);
    }
}
```

## رموز HTTP والاستثناءات

| HTTP | الاستثناء | رمز الخطأ | رسالة |
|------|-----------|-----------|--------|
| 422 | InvalidCoordinatesException | INVALID_COORDINATES | إحداثيات غير صالحة |
| 404 | NoAgentsNearbyException | NO_AGENTS_NEARBY | لا يوجد وكلاء قريبين |
| 503 | GeolocationFailedException | GEOLOCATION_FAILED | فشل تحديد الموقع |
| 429 | ThrottleRequestsException | TOO_MANY_REQUESTS | طلبات كثيرة |
| 401 | AuthenticationException | UNAUTHENTICATED | غير مصرح |
| 500 | \Exception | INTERNAL_ERROR | خطأ داخلي |

## استخدام الاستثناءات في AgentMapService

```php
<?php

use App\Exceptions\InvalidCoordinatesException;
use App\Exceptions\NoAgentsNearbyException;
use App\Exceptions\GeolocationFailedException;

class AgentMapService
{
    public function findNearbyAgents(float $lat, float $lng, int $radiusKm, array $filters = []): array
    {
        // تحقق من صحة الإحداثيات
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new InvalidCoordinatesException(
                'الإحداثيات خارج النطاق المسموح.'
            );
        }

        try {
            $agents = $this->spatialQuery($lat, $lng, $radiusKm, $filters);

            if ($agents->isEmpty()) {
                throw new NoAgentsNearbyException(
                    'لا يوجد وكلاء ضمن ' . $radiusKm . ' كم.',
                    suggestedRadius: $radiusKm * 2
                );
            }

            return $this->formatResponse($agents);
        } catch (\Illuminate\Database\QueryException $e) {
            throw new GeolocationFailedException(
                'فشل الاستعلام المكاني.',
                reason: 'database_error'
            );
        }
    }

    public function updateLocation(int $agentId, float $lat, float $lng): void
    {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidCoordinatesException('خط العرض غير صالح.');
        }

        if ($lng < -180 || $lng > 180) {
            throw new InvalidCoordinatesException('خط الطول غير صالح.');
        }

        // ... تحديث الموقع
    }
}
```
