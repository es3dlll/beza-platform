# 11 - الأحداث والمستمعون (Events & Listeners)

## الأحداث (Events)
```php
<?php
namespace AppEvents;
use AppModelsPaymentLink;
use IlluminateFoundationEventsDispatchable;

class PaymentLinkCreated { use Dispatchable; public function __construct(public readonly PaymentLink $link) {} }
class PaymentCompleted { use Dispatchable; public function __construct(public readonly PaymentLink $link) {} }
class PaymentLinkExpired { use Dispatchable; public function __construct(public readonly PaymentLink $link) {} }
class PaymentLinkCancelled { use Dispatchable; public function __construct(public readonly PaymentLink $link) {} }
class PaymentFailed { use Dispatchable; public function __construct(public readonly PaymentLink $link, public readonly string $reason) {} }
```

## المستمعون (Listeners)
```php
<?php
namespace AppListeners;
use AppEventsPaymentCompleted;
use AppNotificationsPaymentReceived;

class SendPaymentNotification {
    public function handle(PaymentCompleted $event): void {
        $merchant = $event->link->merchant;
        $merchant->user->notify(new PaymentReceived($event->link));
    }
}

class SendPaymentWebhook {
    public function handle(PaymentCompleted $event): void {
        $link = $event->link;
        if ($link->redirect_url) {
            Http::timeout(10)->retry(3, 100)->post($link->redirect_url . '/webhook', [
                'token' => $link->token, 'amount' => $link->amount,
                'currency' => $link->currency, 'status' => 'completed',
            ]);
        }
    }
}
```

## EventServiceProvider
```php
protected $listen = [
    PaymentLinkCreated::class => [],
    PaymentCompleted::class => [SendPaymentNotification::class, SendPaymentWebhook::class],
    PaymentLinkExpired::class => [LogExpiredLink::class],
    PaymentLinkCancelled::class => [LogCancelledLink::class],
];
```

الملخص: الأحداث تفصل منطق الدفع عن الإشعارات والويبهوك، مما يسهل الصيانة والتوسع.
