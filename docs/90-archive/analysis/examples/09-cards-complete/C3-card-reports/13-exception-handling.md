# 13 - معالجة الاستثناءات (Exception Handling)

## Custom Exceptions

```php
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class NoDataException extends Exception
{
    public function __construct(string $message = 'لا توجد بيانات متاحة لهذا التقرير')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => [
                'summary' => [
                    'total_amount' => 0,
                    'total_transactions' => 0,
                    'average_amount' => 0,
                ],
                'by_category' => [],
                'transactions' => [],
            ],
        ], 200);
    }
}

class InvalidDateRangeException extends Exception
{
    public function __construct(string $message = 'نطاق التاريخ غير صالح')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'date_from' => ['تاريخ البداية يجب أن يكون قبل تاريخ النهاية'],
                'date_to' => ['تاريخ النهاية يجب أن يكون بعد تاريخ البداية'],
            ],
        ], 422);
    }
}

class ReportTooLargeException extends Exception
{
    public function __construct(
        string $message = 'التقرير كبير جداً، يرجى تضييق نطاق التاريخ',
        public readonly int $maxDays = 90
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => [
                'date_range' => ["الحد الأقصى المسموح به هو {$this->maxDays} يوماً"],
            ],
        ], 422);
    }
}
```

## Exception Handler Registration

```php
<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        NoDataException::class,
    ];

    public function register(): void
    {
        $this->reportable(function (NoDataException $e) {
            // Log if needed
        });
    }

    public function render($request, \Throwable $e)
    {
        if ($request->expectsJson() && method_exists($e, 'render')) {
            return $e->render();
        }

        return parent::render($request, $e);
    }
}
```

## Error Codes Summary

| HTTP | Exception | Arabic Message |
|------|-----------|----------------|
| 200 | NoDataException | لا توجد بيانات متاحة لهذا التقرير |
| 422 | InvalidDateRangeException | نطاق التاريخ غير صالح |
| 422 | ReportTooLargeException | التقرير كبير جداً، يرجى تضييق نطاق التاريخ |
| 403 | CardNotAccessibleException | البطاقة غير متاحة للمستخدم الحالي |
| 404 | CardNotFoundException | البطاقة غير موجودة |
