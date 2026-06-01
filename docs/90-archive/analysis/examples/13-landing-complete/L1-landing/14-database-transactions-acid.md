# 14 - ACID في نماذج الاتصال

## لماذا نحتاج ACID؟

نماذج الاتصال بسيطة ولا تحتاج أقفالاً معقدة، لكن ACID يضمن:

1. **Atomicity**: إذا فشل إرسال الإيميل، لا نفقد رسالة العميل
2. **Consistency**: لا يوجد بريد مكرر في النشرة
3. **Isolation**: اشتراكات متزامنة لا تسبب تعارضاً
4. **Durability**: حتى لو انقطع الكهرباء، الرسالة محفوظة

## الاشتراك في النشرة البريدية

```php
// NewsletterService
public function subscribe(string $email, ?string $name = null, string $source = 'landing'): Subscriber
{
    // التحقق من uniqueness باستخدام unique constraint في DB
    // email فريد (UNIQUE constraint)
    return DB::transaction(function () use ($email, $name, $source) {
        $subscriber = Subscriber::create([
            'email'         => $email,
            'name'          => $name,
            'is_active'     => true,
            'subscribed_at' => now(),
            'source'        => $source,
        ]);

        // إذا فشل dispatch → لا يؤثر على create (لأنه خارج transaction)
        try {
            NewsletterSubscribed::dispatch($subscriber);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال حدث الاشتراك');
        }

        return $subscriber;
    });
}
```

## إرسال نموذج الاتصال

```php
// ContactService
public function submitContact(...): Contact
{
    return DB::transaction(function () use ($name, $email, $subject, $message, $phone) {
        $contact = Contact::create([
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'subject' => $subject,
            'message' => $message,
        ]);

        // Event خارج transaction — فشله لا يلغي الحفظ
        try {
            ContactSubmitted::dispatch($contact);
        } catch (\Throwable $e) {
            Log::warning('فشل إرسال حدث الاتصال');
        }

        return $contact;
    });
}
```

## ضمانات ACID

| المبدأ | كيف يتحقق |
|--------|----------|
| Atomicity | `DB::transaction` — إما أن تنجح `create` أو تفشل |
| Consistency | `email UNIQUE` في جدول subscribers |
| Isolation | DB transaction الافتراضي (READ COMMITTED) كافٍ |
| Durability | InnoDB + binlog — البيانات محفوظة بعد COMMIT |

## سيناريوهات الفشل

| السيناريو | ماذا يحدث؟ | التعافي |
|-----------|-----------|---------|
| محاولة اشتراك بنفس البريد مرتين | `UNIQUE constraint violation` → `DuplicateSubscriptionException` | يُعلم المستخدم |
| انقطاع الكهرباء بعد create وقبل dispatch | ROLLBACK → لم يتم الحفظ | المستخدم يعيد المحاولة |
| فشل إرسال الإيميل التحذيري | يتم تسجيل الخطأ فقط | لا يؤثر على العميل |
