# 12 - نظام الإشعارات (Notification System)

## PurchaseConfirmedNotification

```php
<?php
// app/Notifications/PurchaseConfirmedNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $commodityName,
        public readonly float  $grams,
        public readonly float  $totalUsd,
        public readonly float  $fee,
        public readonly string $referenceNumber,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تأكيد شراء ' . $this->commodityName)
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('تم شراء ' . number_format($this->grams, 4) . ' جرام من ' . $this->commodityName)
            ->line('المبلغ الإجمالي: $' . number_format($this->totalUsd, 2))
            ->line('الرسوم: $' . number_format($this->fee, 2))
            ->line('الرقم المرجعي: ' . $this->referenceNumber)
            ->action('عرض المحفظة', url('/gold'))
            ->line('شكراً لاستخدامك Beza!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'تم شراء ' . $this->commodityName,
            'body'    => number_format($this->grams, 4) . ' جرام - $' . number_format($this->totalUsd, 2),
            'type'    => 'commodity_purchase',
            'ref'     => $this->referenceNumber,
        ];
    }

    /**
     * إرسال عبر FCM (Firebase Cloud Messaging)
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'notification' => [
                'title' => 'تأكيد شراء ' . $this->commodityName,
                'body'  => 'تم شراء ' . number_format($this->grams, 4) . ' جرام بقيمة $' . number_format($this->totalUsd, 2),
            ],
            'data' => [
                'type'      => 'commodity_purchase',
                'reference' => $this->referenceNumber,
                'screen'    => 'GoldScreen',
            ],
        ];
    }
}
```

## SaleConfirmedNotification

```php
<?php
// app/Notifications/SaleConfirmedNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaleConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $commodityName,
        public readonly float  $grams,
        public readonly float  $totalUsd,
        public readonly float  $fee,
        public readonly string $referenceNumber,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->fcm_token) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تأكيد بيع ' . $this->commodityName)
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('تم بيع ' . number_format($this->grams, 4) . ' جرام من ' . $this->commodityName)
            ->line('المبلغ المستلم: $' . number_format($this->totalUsd - $this->fee, 2))
            ->line('الرسوم: $' . number_format($this->fee, 2))
            ->line('الرقم المرجعي: ' . $this->referenceNumber)
            ->action('عرض الرصيد', url('/wallet'))
            ->line('شكراً لاستخدامك Beza!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'تم بيع ' . $this->commodityName,
            'body'    => number_format($this->grams, 4) . ' جرام - المستلم $' . number_format($this->totalUsd - $this->fee, 2),
            'type'    => 'commodity_sale',
            'ref'     => $this->referenceNumber,
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'notification' => [
                'title' => 'تأكيد بيع ' . $this->commodityName,
                'body'  => 'تم بيع ' . number_format($this->grams, 4) . ' جرام واستلام $' . number_format($this->totalUsd - $this->fee, 2),
            ],
            'data' => [
                'type'      => 'commodity_sale',
                'reference' => $this->referenceNumber,
                'screen'    => 'WalletScreen',
            ],
        ];
    }
}
```

## PriceAlertNotification

```php
<?php
// app/Notifications/PriceAlertNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PriceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $commodityName,
        public readonly float  $currentPrice,
        public readonly float  $targetPrice,
        public readonly string $direction,
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable->fcm_token ? ['fcm', 'database'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تنبيه سعر ' . $this->commodityName,
            'body'  => 'سعر ' . $this->commodityName . ' ' . $this->direction . ' $' . number_format($this->currentPrice, 2),
            'type'  => 'price_alert',
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'notification' => [
                'title' => '⚡ تنبيه سعر ' . $this->commodityName,
                'body'  => 'السعر الآن $' . number_format($this->currentPrice, 2) . ' (المستهدف: $' . number_format($this->targetPrice, 2) . ')',
            ],
            'data' => [
                'type'  => 'price_alert',
                'screen'=> 'GoldScreen',
            ],
        ];
    }
}
```

## WeeklyHoldingStatementNotification

```php
<?php
// app/Notifications/WeeklyHoldingStatementNotification.php

namespace App\Notifications;

use App\Models\CommodityHolding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyHoldingStatementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CommodityHolding $goldHolding,
        public readonly CommodityHolding $silverHolding,
        public readonly float $totalValueUsd,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $goldValue = $this->goldHolding ? $this->goldHolding->current_value_usd : 0;
        $silverValue = $this->silverHolding ? $this->silverHolding->current_value_usd : 0;

        return (new MailMessage)
            ->subject('كشف حساب الأسبوعي - الذهب والفضة')
            ->greeting('مرحباً ' . $notifiable->name)
            ->line('قيمة محفظتك من الذهب والفضة لهذا الأسبوع:')
            ->line('الذهب: ' . number_format($this->goldHolding?->grams ?? 0, 4) . ' جم - $' . number_format($goldValue, 2))
            ->line('الفضة: ' . number_format($this->silverHolding?->grams ?? 0, 4) . ' جم - $' . number_format($silverValue, 2))
            ->line('الإجمالي: $' . number_format($this->totalValueUsd, 2))
            ->action('عرض المحفظة', url('/gold'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'كشف حساب الأسبوعي',
            'body'  => 'قيمة محفظتك: $' . number_format($this->totalValueUsd, 2),
            'type'  => 'weekly_statement',
        ];
    }
}
```

## ملخص الإشعارات

| الإشعار | المناسبة | قنوات الإرسال |
|---------|---------|---------------|
| PurchaseConfirmedNotification | بعد كل عملية شراء | FCM + Mail + Database |
| SaleConfirmedNotification | بعد كل عملية بيع | FCM + Database |
| PriceAlertNotification | عندما يصل السعر للهدف | FCM + Database |
| WeeklyHoldingStatementNotification | كل يوم اثنين 9:00 صباحاً | Mail + Database |
