# 11 - الأحداث والمستمعين (Events & Listeners)

## Event: ContactSubmitted

```php
<?php
// app/Events/ContactSubmitted.php

namespace App\Events;

use App\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Contact $contact,
    ) {}
}
```

## Event: NewsletterSubscribed

```php
<?php
// app/Events/NewsletterSubscribed.php

namespace App\Events;

use App\Models\Subscriber;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsletterSubscribed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Subscriber $subscriber,
    ) {}
}
```

## Event: MerchantInquirySubmitted

```php
<?php
// app/Events/MerchantInquirySubmitted.php

namespace App\Events;

use App\Models\MerchantInquiry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MerchantInquirySubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MerchantInquiry $inquiry,
    ) {}
}
```

## Listener: SendContactAutoReply

```php
<?php
// app/Listeners/Landing/SendContactAutoReply.php

namespace App\Listeners\Landing;

use App\Events\ContactSubmitted;
use App\Notifications\ContactAutoReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendContactAutoReply implements ShouldQueue
{
    use InteractsWithQueue;

    public $maxAttempts = 3;

    public function handle(ContactSubmitted $event): void
    {
        try {
            // إنشاء مستخدم مؤقت للإشعار
            $notifiable = new \stdClass();
            $notifiable->email = $event->contact->email;
            $notifiable->name = $event->contact->name;

            \Illuminate\Support\Facades\Notification::route('mail', $event->contact->email)
                ->notify(new ContactAutoReply($event->contact));
        } catch (\Throwable $e) {
            Log::error('فشل إرسال الرد التلقائي للاتصال', [
                'contact_id' => $event->contact->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function failed(ContactSubmitted $event, \Throwable $exception): void
    {
        Log::critical('فشل إرسال الرد التلقائي بعد 3 محاولات', [
            'contact_id' => $event->contact->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
```

## Listener: NotifyAdminNewSubscriber

```php
<?php
// app/Listeners/Landing/NotifyAdminNewSubscriber.php

namespace App\Listeners\Landing;

use App\Events\NewsletterSubscribed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminNewSubscriber implements ShouldQueue
{
    public function handle(NewsletterSubscribed $event): void
    {
        Log::info('مشترك جديد — إرسال إشعار للإدارة', [
            'email'  => $event->subscriber->email,
            'source' => $event->subscriber->source,
        ]);

        // يمكن إرسال بريد للإدارة
        // \Illuminate\Support\Facades\Notification::route('mail', 'admin@beza.com')
        //     ->notify(new NewSubscriberAlert($event->subscriber));
    }
}
```

## تسجيل الأحداث

```php
<?php
// app/Providers/EventServiceProvider.php

namespace App\Providers;

use App\Events\ContactSubmitted;
use App\Events\MerchantInquirySubmitted;
use App\Events\NewsletterSubscribed;
use App\Listeners\Landing\NotifyAdminNewSubscriber;
use App\Listeners\Landing\SendContactAutoReply;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ContactSubmitted::class => [
            SendContactAutoReply::class,
        ],
        NewsletterSubscribed::class => [
            NotifyAdminNewSubscriber::class,
        ],
        MerchantInquirySubmitted::class => [
            \App\Listeners\Landing\NotifySalesTeam::class,
        ],
    ];
}
```

## Queue

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'database'),

// تشغيل العامل
// php artisan queue:work --queue=default --tries=3 --delay=2
```

### لماذا Async؟
| السبب | التفصيل |
|-------|---------|
| سرعة الاستجابة | المستخدم لا ينتظر حتى ترسل الإيميلات |
| Fault tolerance | فشل الرد التلقائي لا يلغي نجاح إرسال النموذج |
| Retry | Queue يعيد المحاولة تلقائياً |
