# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: TransactionCompleted

```php
<?php
// app/Events/TransactionCompleted.php

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
        public readonly User       $sender,
        public readonly User       $receiver,
    ) {}
}
```

## Listener: SendTransactionNotification

```php
<?php
// app/Listeners/SendTransactionNotification.php

namespace App\Listeners;

use App\Events\TransactionCompleted;
use App\Notifications\TransactionReceived;
use App\Notifications\TransactionSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendTransactionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    // إذا فشلت 3 مرات — نضعها في failed_jobs
    public $maxAttempts = 3;

    // تأخير 2 ثانية قبل التنفيذ
    public function __construct()
    {
        $this->delay = now()->addSeconds(2);
    }

    public function handle(TransactionCompleted $event): void
    {
        $transaction = $event->transaction;
        $sender      = $event->sender;
        $receiver    = $event->receiver;

        try {
            // إشعار للمرسل — تم خصم المبلغ
            $sender->notify(new TransactionSent(
                transaction: $transaction,
                receiverName: $receiver->name,
            ));
        } catch (\Throwable $e) {
            Log::error('فشل إشعار المرسل', [
                'user_id' => $sender->id,
                'error'   => $e->getMessage(),
            ]);
        }

        try {
            // إشعار للمستلم — تم استلام المبلغ
            $receiver->notify(new TransactionReceived(
                transaction: $transaction,
                senderName: $sender->name,
            ));
        } catch (\Throwable $e) {
            Log::error('فشل إشعار المستلم', [
                'user_id' => $receiver->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * معالجة الفشل — تسجيل للتحقق
     */
    public function failed(TransactionCompleted $event, \Throwable $exception): void
    {
        Log::critical('فشل إرسال إشعار التحويل بعد 3 محاولات', [
            'transaction_id' => $event->transaction->id,
            'error'          => $exception->getMessage(),
        ]);
    }
}
```

## تسجيل الـ Event & Listener

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\TransactionCompleted;
use App\Listeners\SendTransactionNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TransactionCompleted::class => [
            SendTransactionNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
```

## Queue (لتشغيل الـ Listener بشكل Async)

```php
// config/queue.php — استخدام database queue
'default' => env('QUEUE_CONNECTION', 'database'),

// تشغيل العامل
// php artisan queue:work --queue=default --tries=3 --delay=2
```

### لماذا Async؟
| السبب | التفصيل |
|-------|---------|
| سرعة الاستجابة | المستخدم لا ينتظر حتى ترسل الإشعارات |
| Fault tolerance | فشل الإشعار لا يلغي نجاح المعاملة |
| Retry | Queue يعيد المحاولة تلقائياً |
| Scalability | يمكن إضافة Workers متعددة |
