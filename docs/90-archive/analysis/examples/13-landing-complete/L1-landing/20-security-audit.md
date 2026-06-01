# 20 - أمان الموقع التسويقي (Security Audit)

## 1. Input Validation

```php
// ❌ خطأ: قبول أي إدخال
Contact::create($request->all());

// ✅ صحيح: تحديد الحقول المسموحة
Contact::create($request->validated()); // من FormRequest

// ✅ صحيح: validation rules في FormRequest
'email' => ['required', 'email', 'max:100'],
'message' => ['required', 'string', 'min:10', 'max:5000'],
```

## 2. SQL Injection

```php
// ❌ خطأ
DB::statement("INSERT INTO contacts (email) VALUES ('{$email}')");

// ✅ صحيح — Eloquent
Contact::create($request->validated());

// ✅ صحيح — Parameter binding
DB::insert('INSERT INTO contacts (email) VALUES (?)', [$email]);
```

## 3. XSS Prevention

```tsx
// ✅ Next.js: افتراضياً آمن
// React يهرب HTML تلقائياً

// ❌ خطأ: استخدام dangerouslySetInnerHTML دون داع
<div dangerouslySetInnerHTML={{ __html: userContent }} />

// ✅ صحيح: استخدام text content
<div>{userContent}</div>
```

## 4. Rate Limiting

```php
// routes/api.php
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/contact', [ContactController::class, 'store']);
    Route::post('/newsletter/subscribe', [SubscribeController::class, 'subscribe']);
});
```

| الإعداد | max_attempts | decay_minutes | السبب |
|---------|-------------|---------------|-------|
| contact | 10 | 1 | منع إغراق النموذج |
| subscribe | 10 | 1 | منع سبام الاشتراكات |
| merchant-inquiry | 5 | 60 | استفسارات حساسة — حد أضيق |

## 5. Honeypot (Anti-Spam)

```tsx
// حقل مخفي في النموذج
<input
  type="text"
  name="website"
  className="honeypot"
  tabIndex={-1}
  autoComplete="off"
  style={{ display: 'none' }}
/>
```

```css
/* إخفاء الحقل */
.honeypot {
  position: absolute !important;
  left: -9999px !important;
}
```

## 6. CSRF

```php
// API باستخدام Bearer Token (إذا كان المستخدم مسجلاً)
// Public endpoints (contact, subscribe) — لا تحتاج CSRF
// لأنها لا تستخدم cookies للمصادقة
```

## 7. CORS

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://beza.com',           // الإنتاج
        'https://www.beza.com',
        'http://localhost:3000',       // التطوير
    ],
    'allowed_headers' => ['Content-Type', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 86400,
];
```

## 8. HTTPS

```nginx
server {
    listen 443 ssl http2;
    server_name beza.com www.beza.com;

    ssl_certificate /etc/ssl/certs/beza.com.crt;
    ssl_certificate_key /etc/ssl/private/beza.com.key;

    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # التوجيه إلى Next.js (Vercel)
    location / {
        proxy_pass https://beza-app.vercel.app;
    }
}
```

## 9. Content Security Policy (CSP)

```tsx
// next.config.js
const csp = `
  default-src 'self';
  script-src 'self' 'unsafe-inline' 'unsafe-eval';
  style-src 'self' 'unsafe-inline';
  img-src 'self' https: data:;
  font-src 'self';
  connect-src 'self' https://api.beza.com;
  frame-ancestors 'none';
`;

module.exports = {
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          { key: 'Content-Security-Policy', value: csp.replace(/\s+/g, ' ') },
        ],
      },
    ];
  },
};
```

## 10. Information Disclosure

```tsx
// ❌ عدم كشف معلومات حساسة في الخطأ
// production: لا تظهر stack trace
// API: رسائل خطأ عامة

// ✅ صحيح
if (!$contact) {
    return response()->json([
        'success' => false,
        'message' => 'حدث خطأ',
    ], 404);
}
```

## 11. Logging

```php
// تسجيل كل محاولات النماذج
Log::info('نموذج اتصل بنا', [
    'email' => $request->input('email'),
    'ip'    => $request->ip(),
    'agent' => $request->userAgent(),
]);

// تسجيل حالات الشبهة (Spam محتمل)
if ($this->isSuspicious($request)) {
    Log::warning('نشاط مشبوه في نموذج الاتصال', [
        'ip' => $request->ip(),
    ]);
}
```

## 12. قائمة التحقق الأمني

| # | البند | الحالة |
|---|-------|--------|
| 1 | جميع المدخلات موثقة (FormRequest) | ✅ |
| 2 | Parameterized SQL / Eloquent | ✅ |
| 3 | XSS محمي (React يهرب HTML) | ✅ |
| 4 | Rate Limiting (10/دقيقة) | ✅ |
| 5 | Honeypot لمكافحة Spam | ✅ |
| 6 | CORS مقيّد | ✅ |
| 7 | HTTPS مع HSTS | ✅ |
| 8 | CSP headers | ✅ |
| 9 | لا معلومات حساسة في الأخطاء | ✅ |
| 10 | Audit logging | ✅ |
| 11 | لا Mass assignment | ✅ |
| 12 | CAPTCHA على نموذج الاتصال | ✅ |
| 13 | Cloudflare WAF | ✅ |
| 14 | تدقيق أمني دوري | 📋 ربع سنوي |
