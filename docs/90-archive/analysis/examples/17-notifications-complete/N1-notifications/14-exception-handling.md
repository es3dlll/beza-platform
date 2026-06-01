# 14 - معالجة الأخطاء (Exception Handling)

## Custom Exceptions

```php
<?php

namespace App\Exceptions;

use Exception;

class NotificationFailedException extends Exception
{
    public function __construct(
        string $channel,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            "فشل إرسال الإشعار عبر {$channel}: {$message}",
            0,
            $previous
        );
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'channel' => $channel ?? 'unknown',
        ], 500);
    }
}

class InvalidNotificationChannelException extends Exception
{
    public function __construct(string $channel)
    {
        parent::__construct("القناة {$channel} غير مدعومة");
    }
}
```

## Handler Registration

```php
<?php
// App\Exceptions\Handler

public function register(): void
{
    $this->reportable(function (NotificationFailedException $e) {
        Log::channel('notifications')->error($e->getMessage(), [
            'channel' => $e->channel,
            'trace' => $e->getTraceAsString(),
        ]);
    });
}
```

## Retry Logic

```php
<?php

use Illuminate\Support\Facades\Queue;

// config/queue.php - إعادة المحاولة للإشعارات
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'notifications',
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => true,
    ],
],

// في Job
public int $tries = 5;
public array $backoff = [10, 30, 60, 120, 300]; // ثوانٍ
```

## نهج Failover

```php
// إذا فشلت قناة، جرب القناة التالية
$channels = $template->channels;
foreach ($channels as $channel) {
    try {
        $result = $this->channels[$channel]->send(...);
        if ($result['success']) break;
    } catch (\Throwable $e) {
        Log::warning("Failover from {$channel}");
    }
}
```
