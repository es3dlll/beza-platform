# 13 - معالجة الاستثناءات (Exception Handling) - الوكلاء

## استثناءات مخصصة للوكيل

```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AgentRegistrationFailedException extends Exception
{
    public function __construct(
        string $message = 'فشلت عملية تسجيل الوكيل',
        private ?int $agentRequestId = null,
        private array $context = [],
        int $code = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        $this->logError();

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'AGENT_REGISTRATION_FAILED',
            'agent_request_id' => $this->agentRequestId,
        ], $this->getCode() ?: 500);
    }

    private function logError(): void
    {
        Log::error('فشل تسجيل الوكيل', [
            'agent_request_id' => $this->agentRequestId,
            'context' => $this->context,
            'exception' => $this->getMessage(),
        ]);
    }
}

class AgentAlreadyExistsException extends Exception
{
    public function __construct(
        private int $userId,
        string $message = 'المستخدم مسجل بالفعل كوكيل',
        int $code = 409,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        Log::warning('محاولة تسجيل وكيل موجود مسبقاً', [
            'user_id' => $this->userId,
        ]);

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'AGENT_ALREADY_EXISTS',
            'user_id' => $this->userId,
        ], 409);
    }
}

class LocationOutOfBoundsException extends Exception
{
    public function __construct(
        private float $lat,
        private float $lng,
        string $message = 'الموقع خارج النطاق المسموح به',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        Log::warning('موقع خارج النطاق', [
            'lat' => $this->lat,
            'lng' => $this->lng,
        ]);

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'LOCATION_OUT_OF_BOUNDS',
            'errors' => [
                'location' => [
                    'يجب أن يكون الموقع داخل حدود المملكة العربية السعودية.',
                    "خط العرض {$this->lat} غير مسموح به.",
                    "خط الطول {$this->lng} غير مسموح به.",
                ],
            ],
        ], 422);
    }
}

class CommissionRateInvalidException extends Exception
{
    public function __construct(
        private ?float $rate = null,
        string $message = 'نسبة العمولة غير صالحة',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        Log::warning('نسبة عمولة غير صالحة', [
            'rate' => $this->rate,
        ]);

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'COMMISSION_RATE_INVALID',
            'errors' => [
                'commission_rate' => [
                    'نسبة العمولة يجب أن تكون بين 0.1% و 10% (0.001 إلى 0.100).',
                    $this->rate ? "القيمة المدخلة: {$this->rate}" : null,
                ],
            ],
        ], 422);
    }
}

class InsufficientBalanceException extends Exception
{
    public function __construct(
        private float $balance,
        private float $required,
        private int $agentId,
        string $message = 'الرصيد غير كافٍ لإتمام العملية',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render(): JsonResponse
    {
        Log::warning('رصيد غير كافٍ للوكيل', [
            'agent_id' => $this->agentId,
            'balance' => $this->balance,
            'required' => $this->required,
        ]);

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'code' => 'INSUFFICIENT_BALANCE',
            'data' => [
                'current_balance' => $this->balance,
                'required_amount' => $this->required,
                'shortfall' => $this->required - $this->balance,
            ],
        ], 422);
    }
}

class DailyLimitExceededException extends Exception
{
    public function __construct(
        private float $limit,
        private float $currentTotal,
        private float $attemptedAmount,
        private int $agentId,
        string $message = 'تم تجاوز الحد اليومي المسموح به',
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
            'code' => 'DAILY_LIMIT_EXCEEDED',
            'data' => [
                'daily_limit' => $this->limit,
                'used_today' => $this->currentTotal,
                'attempted' => $this->attemptedAmount,
                'remaining' => $this->limit - $this->currentTotal,
            ],
        ], 422);
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

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($e instanceof AgentRegistrationFailedException) {
                Log::channel('agent')->error($e->getMessage(), $e->getContext());
            }
        });

        $this->renderable(function (AgentRegistrationFailedException $e) {
            return $e->render();
        });

        $this->renderable(function (AgentAlreadyExistsException $e) {
            return $e->render();
        });

        $this->renderable(function (LocationOutOfBoundsException $e) {
            return $e->render();
        });

        $this->renderable(function (CommissionRateInvalidException $e) {
            return $e->render();
        });

        $this->renderable(function (InsufficientBalanceException $e) {
            return $e->render();
        });

        $this->renderable(function (DailyLimitExceededException $e) {
            return $e->render();
        });
    }
}
```

## قائمة الاستثناءات

| الاستثناء | كود HTTP | كود الخطأ | الوصف |
|-----------|----------|-----------|-------|
| AgentRegistrationFailedException | 500 | AGENT_REGISTRATION_FAILED | فشل عام في تسجيل الوكيل |
| AgentAlreadyExistsException | 409 | AGENT_ALREADY_EXISTS | المستخدم موجود بالفعل كوكيل |
| LocationOutOfBoundsException | 422 | LOCATION_OUT_OF_BOUNDS | الموقع خارج المملكة |
| CommissionRateInvalidException | 422 | COMMISSION_RATE_INVALID | نسبة العمولة غير صالحة |
| InsufficientBalanceException | 422 | INSUFFICIENT_BALANCE | رصيد غير كافٍ |
| DailyLimitExceededException | 422 | DAILY_LIMIT_EXCEEDED | تجاوز الحد اليومي |
