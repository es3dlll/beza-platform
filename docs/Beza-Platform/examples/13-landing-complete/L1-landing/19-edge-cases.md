# 19 - حالات الحافة + سيناريوهات خطأ (Edge Cases)

## 1. اشتراك بنفس البريد مرتين

**المشكلة**: مستخدم يحاول الاشتراك في النشرة البريدية بنفس البريد مرتين.

**الحل**: `UNIQUE` constraint في DB + رسالة واضحة:

```php
// SubscribeRequest
'email' => ['required', 'email', 'unique:subscribers,email'],

// رسالة الخطأ
'email.unique' => 'هذا البريد مسجل بالفعل'
```

## 2. إرسال نموذج اتصال ببيانات ضخمة

**المشكلة**: إرسال رسالة بطول 100,000 حرف.

**الحل**: تحديد `max:5000` في validation + `TEXT` في DB:

```php
'message' => ['required', 'string', 'min:10', 'max:5000'],
```

## 3. هجوم Spam (نموذج الاتصال)

**المشكلة**: بوتات تملأ النموذج برسائل غير مرغوب فيها.

**الحلول**:
1. Honeypot (حقل مخفي لا يراه البشر)
2. Rate Limiting
3. CAPTCHA (للإصدار القادم)

```php
// Honeypot في النموذج
<input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off" />

// التحقق
if ($request->filled('website')) {
    // هذا بوت — تجاهل
    return response()->json(['success' => true]); // لا تخبر البوت
}
```

## 4. بريد إلكتروني بتنسيقات غريبة

**المشكلة**: عناوين بريد مثل `user+tag@beza.example` أو `"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"@strange.beza.example`.

**الحل**: استخدام `email` validation rule الخاص بـ Laravel — يغطي 99% من الحالات.

## 5. انقطاع خدمة البريد (SMTP Down)

**المشكلة**: خادم SMTP لا يستجيب عند محاولة إرسال الرد التلقائي.

**الحل**: Queue + Retry + عدم إيقاف الاستجابة:

```php
// حدث في Queue — يعيد المحاولة 3 مرات
class SendContactAutoReply implements ShouldQueue
{
    public $maxAttempts = 3;

    public function handle(ContactSubmitted $event): void
    {
        // ...
    }

    public function failed(ContactSubmitted $event, \Throwable $e): void
    {
        Log::critical('فشل الرد التلقائي بعد 3 محاولات');
    }
}
```

## 6. تزامن عالٍ على نموذج الاشتراك

**المشكلة**: 100 مستخدم يشتركون في نفس اللحظة.

**الحل**: `UNIQUE constraint` يضمن عدم حدوث تكرار — `DuplicateSubscriptionException` للمستخدم الثاني.

## 7. إلغاء الاشتراك لبريد غير موجود

**المشكلة**: مستخدم يحاول إلغاء الاشتراك لبريد غير مسجل.

**الحل**: لا تظهر خطأ — فقط اعتبره منجزاً:

```php
public function unsubscribe(string $email): void
{
    $subscriber = Subscriber::where('email', $email)->first();
    if ($subscriber) {
        $subscriber->unsubscribe();
    }
    // لا تُظهر خطأ — أمنياً أفضل
}
```

## 8. رابط تحميل معطل

**المشكلة**: رابط متجر التطبيقات لا يعمل (تم تحديث الرابط).

**الحل**: استخدام روابط ديناميكية من متغيرات البيئة:

```tsx
const PLAY_STORE_URL = process.env.NEXT_PUBLIC_PLAY_STORE_URL;
const APP_STORE_URL = process.env.NEXT_PUBLIC_APP_STORE_URL;
```

## 9. تغيير محتوى الصفحة (Static Site)

**المشكلة**: المحتوى ثابت (يُبنى عند النشر) — لا يمكن تغييره بسرعة.

**الحل**: استخدام headless CMS (مثل Contentlayer أو MDX) للمحتوى القابل للتعديل:

```tsx
// app/page.tsx
import { getHomePageContent } from '@/lib/content';

export default async function HomePage() {
  const content = await getHomePageContent();
  return <Hero content={content.hero} />;
}
```

## 10. دعم المتصفحات القديمة

**المشكلة**: متصفحات قديمة لا تدعم Tailwind أو CSS Grid.

**الحل**: transpile + polyfills في next.config.js + تدرج CSS:

```js
// next.config.js
module.exports = {
  transpilePackages: [],
  experimental: { optimizeCss: true },
};
```

## 11. تحميل الصور ببطء

**المشكلة**: صور كبيرة تبطئ تحميل الصفحة.

**الحل**: استخدام Next.js Image component مع تحسين تلقائي:

```tsx
import Image from 'next/image';

<Image
  src="/hero-image.webp"
  alt="Beza App"
  width={600}
  height={400}
  priority
  loading="eager"
/>
```

## 12. تحسين SEO للصفحات الفرعية

**المشكلة**: صفحة `/merchants` و `/agents` ليس لها Meta tags مميزة.

**الحل**: `generateMetadata()` لكل صفحة:

```tsx
// app/merchants/page.tsx
export const metadata = {
  title: 'Beza للتجار | افتح متجرك الإلكتروني',
  description: 'انضم إلى Beza كتاجر وابدأ البيع عبر الإنترنت',
};
```

## جدول ملخص حالات الحافة

| # | الحالة | النتيجة | مستوى المعالجة |
|---|--------|---------|---------------|
| 1 | اشتراك مكرر | رفض مع رسالة | DB (UNIQUE) + Validation |
| 2 | رسالة ضخمة | رفض مع تحديد حد | Validation |
| 3 | Spam بوت | تجاهل (Honeypot) | Application |
| 4 | بريد غريب | قبول (validation) | Validation |
| 5 | SMTP معطل | إعادة محاولة عبر Queue | Queue + Log |
| 6 | تزامن عالٍ | UNIQUE يحمي | DB |
| 7 | إلغاء لبريد غير موجود | نجاح صامت | Application |
| 8 | رابط تحميل معطل | متغيرات بيئة | Config |
| 9 | محتوى ثابت | CMS / MDX | Architecture |
| 10 | متصفح قديم | Polyfills | Build |
| 11 | صور بطيئة | Next.js Image | Optimization |
| 12 | SEO للصفحات الفرعية | generateMetadata() | SEO |
