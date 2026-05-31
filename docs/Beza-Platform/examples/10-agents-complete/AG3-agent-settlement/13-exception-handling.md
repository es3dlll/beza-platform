# 13 - معالجة الاستثناءات (Exception Handling)

## استثناءات التسوية

```php
<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * استثناء تجاوز حد التسوية اليومي
 */
class SettlementLimitExceededException extends \RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            $message ?: 'تجاوز الحد اليومي للتسوية المسموح به.'
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'amount' => [$this->getMessage()],
            ],
        ], 422);
    }

    public function getErrorCode(): string
    {
        return 'SETTLEMENT_LIMIT_EXCEEDED';
    }
}
```

```php
<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * استثناء فشل التحويل المصرفي
 */
class BankTransferFailedException extends \RuntimeException
{
    private ?string $transactionRef;

    public function __construct(string $message = '', ?string $transactionRef = null)
    {
        parent::__construct(
            $message ?: 'فشلت عملية التحويل المصرفي.'
        );
        $this->transactionRef = $transactionRef;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'bank_transfer' => [$this->getMessage()],
            ],
            'data' => [
                'transaction_ref' => $this->transactionRef,
                'recommendation' => 'يرجى التحقق من معلومات الحساب والمحاولة مرة أخرى.',
            ],
        ], 502);
    }

    public function getErrorCode(): string
    {
        return 'BANK_TRANSFER_FAILED';
    }
}
```

```php
<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * استثناء وجود طلب تسوية معلق
 */
class PendingSettlementExistsException extends \RuntimeException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            $message ?: 'يوجد طلب تسوية قيد المعالجة حالياً.'
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'pending' => [$this->getMessage()],
            ],
        ], 409);
    }

    public function getErrorCode(): string
    {
        return 'PENDING_SETTLEMENT_EXISTS';
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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        SettlementLimitExceededException::class,
        PendingSettlementExistsException::class,
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($e instanceof BankTransferFailedException) {
                \Illuminate\Support\Facades\Log::critical('فشل تحويل مصرفي', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
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
                    'message' => 'خطأ في التحقق من البيانات',
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
                    'message' => 'الموارد غير موجودة',
                ], 404);
            }

            if ($e instanceof HttpException) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
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
| 422 | SettlementLimitExceededException | SETTLEMENT_LIMIT_EXCEEDED | تجاوز الحد اليومي |
| 502 | BankTransferFailedException | BANK_TRANSFER_FAILED | فشل التحويل المصرفي |
| 409 | PendingSettlementExistsException | PENDING_SETTLEMENT_EXISTS | طلب معلق موجود |
| 422 | InvalidArgumentException | INVALID_AMOUNT | مبلغ غير صالح |
| 404 | NotFoundHttpException | NOT_FOUND | الموارد غير موجودة |
| 401 | AuthenticationException | UNAUTHENTICATED | غير مصرح |
| 429 | ThrottleRequestsException | TOO_MANY_REQUESTS | طلبات كثيرة |
| 500 | \Exception | INTERNAL_ERROR | خطأ داخلي |

## استخدام الاستثناءات في الخدمة

```php
<?php

namespace App\Services;

use App\Exceptions\SettlementLimitExceededException;
use App\Exceptions\BankTransferFailedException;
use App\Exceptions\PendingSettlementExistsException;

class AgentSettlementService
{
    public function requestSettlement($agent, array $data): array
    {
        $validator = new Validators\SettlementValidator();

        $validator->validateAmount($data['amount'], $data['currency']);
        $validator->validatePendingRequests($agent->id);
        $validator->validateAgentBalance($agent->id, $data['amount']);

        return DB::transaction(function () use ($agent, $data) {
            // إنشاء طلب التسوية
        });
    }

    public function processBankTransfer(int $settlementId): array
    {
        try {
            return $this->walletService->processBankTransfer($settlementId);
        } catch (BankTransferFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new BankTransferFailedException(
                'فشل غير متوقع في التحويل: ' . $e->getMessage()
            );
        }
    }
}
```
