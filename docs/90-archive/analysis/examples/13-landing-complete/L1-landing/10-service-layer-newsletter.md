# 10 - Service Layer — النشرة البريدية

## NewsletterService

```php
<?php
// app/Services/Landing/NewsletterService.php

namespace App\Services\Landing;

use App\Events\NewsletterSubscribed;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Log;

class NewsletterService
{
    /**
     * الاشتراك في النشرة البريدية
     *
     * @param string      $email
     * @param string|null $name
     * @param string      $source  (footer, hero, modal, ...)
     *
     * @return Subscriber
     */
    public function subscribe(
        string  $email,
        ?string $name = null,
        string  $source = 'landing',
    ): Subscriber {
        $subscriber = Subscriber::create([
            'email'         => $email,
            'name'          => $name,
            'is_active'     => true,
            'subscribed_at' => now(),
            'source'        => $source,
        ]);

        try {
            NewsletterSubscribed::dispatch($subscriber);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال حدث NewsletterSubscribed', [
                'subscriber_id' => $subscriber->id,
                'error'         => $e->getMessage(),
            ]);
        }

        Log::info('مشترك جديد في النشرة البريدية', [
            'email'  => $email,
            'source' => $source,
        ]);

        return $subscriber;
    }

    /**
     * إلغاء الاشتراك
     */
    public function unsubscribe(string $email): void
    {
        $subscriber = Subscriber::where('email', $email)->first();

        if ($subscriber) {
            $subscriber->unsubscribe();

            Log::info('إلغاء اشتراك من النشرة البريدية', [
                'email' => $email,
            ]);
        }
    }

    /**
     * عدد المشتركين النشطين
     */
    public function getActiveCount(): int
    {
        return Subscriber::active()->count();
    }
}
```

## تدفق NewsletterService

```
1. التحقق من البريد (unique)
         │
2. Subscriber::create(...)
         │
3. NewsletterSubscribed::dispatch($subscriber)  ← Async
         │
4. Log::info(...)
         │
5. Return success response
```
