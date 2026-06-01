# 12 - نظام الإشعارات (Email)

## إشعار الرد التلقائي (Contact Auto Reply)

```php
<?php
// app/Notifications/ContactAutoReply.php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactAutoReply extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Contact $contact,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('شكراً لتواصلك مع Beza')
            ->greeting('مرحباً ' . $this->contact->name)
            ->line('شكراً لتواصلك مع فريق Beza.')
            ->line('لقد استلمنا رسالتك وسنقوم بالرد عليك في أقرب وقت ممكن.')
            ->line("موضوع رسالتك: {$this->contact->subject}")
            ->line('فريق Beza')
            ->salutation('مع تحيات، فريق Beza');
    }
}
```

## إشعار إدارة المبيعات (طلب تاجر جديد)

```php
<?php
// app/Notifications/NewMerchantInquiry.php

namespace App\Notifications;

use App\Models\MerchantInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMerchantInquiry extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly MerchantInquiry $inquiry,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('طلب تاجر جديد — ' . $this->inquiry->company_name)
            ->greeting('طلب تاجر جديد')
            ->line("الشركة: {$this->inquiry->company_name}")
            ->line("جهة الاتصال: {$this->inquiry->contact_name}")
            ->line("البريد: {$this->inquiry->email}")
            ->line("الهاتف: {$this->inquiry->phone}")
            ->line("نشاط: " . ($this->inquiry->business_type ?? 'غير محدد'))
            ->action('عرض الطلب', url('/admin/inquiries/' . $this->inquiry->id));
    }
}
```

## إشعار ترحيب النشرة البريدية

```php
<?php
// app/Notifications/WelcomeNewsletter.php

namespace App\Notifications;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNewsletter extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Subscriber $subscriber,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('مرحباً بك في نشرة Beza البريدية')
            ->greeting('مرحباً' . ($this->subscriber->name ? ' ' . $this->subscriber->name : '') . '!')
            ->line('شكراً لاشتراكك في النشرة البريدية لمنصة Beza.')
            ->line('ستصلك آخر الأخبار والعروض الحصرية.')
            ->line('يمكنك إلغاء الاشتراك في أي وقت.')
            ->action('زيارة Beza', url('https://beza.com'))
            ->salutation('مع تحيات، فريق Beza');
    }
}
```

## إرسال الإشعارات

```php
// استخدام Notification facade مع route
\Illuminate\Support\Facades\Notification::route('mail', $email)
    ->notify(new ContactAutoReply($contact));

// أو عبر Notifiable trait
$user->notify(new WelcomeNewsletter($subscriber));
```
