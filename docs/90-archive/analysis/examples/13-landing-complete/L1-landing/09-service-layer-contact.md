# 09 - Service Layer — نموذج الاتصال

## ContactService

```php
<?php
// app/Services/Landing/ContactService.php

namespace App\Services\Landing;

use App\Events\ContactSubmitted;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class ContactService
{
    /**
     * معالجة إرسال نموذج الاتصال
     *
     * @param string      $name
     * @param string      $email
     * @param string      $subject
     * @param string      $message
     * @param string|null $phone
     *
     * @return Contact
     */
    public function submitContact(
        string  $name,
        string  $email,
        string  $subject,
        string  $message,
        ?string $phone = null,
    ): Contact {
        // 1. حفظ الرسالة في قاعدة البيانات
        $contact = Contact::create([
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'subject' => $subject,
            'message' => $message,
        ]);

        // 2. تسجيل الحدث
        try {
            ContactSubmitted::dispatch($contact);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال حدث ContactSubmitted', [
                'contact_id' => $contact->id,
                'error'      => $e->getMessage(),
            ]);
        }

        // 3. تسجيل في السجلات
        Log::info('رسالة جديدة من نموذج الاتصال', [
            'contact_id' => $contact->id,
            'email'      => $email,
            'subject'    => $subject,
            'ip'         => request()->ip(),
        ]);

        return $contact;
    }

    /**
     * جلب الرسائل غير المقروءة
     */
    public function getUnreadMessages(): array
    {
        return Contact::unread()
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * تحديد رسالة كمقروءة
     */
    public function markAsRead(int $contactId): void
    {
        $contact = Contact::findOrFail($contactId);
        $contact->markAsRead();
    }
}
```

## تدفق ContactService

```
1. التحقق من صحة البيانات (FormRequest)
         │
2. Contact::create(...)
         │
3. ContactSubmitted::dispatch($contact)  ← Async
         │
4. Log::info(...)
         │
5. Return Contact Resource
```
