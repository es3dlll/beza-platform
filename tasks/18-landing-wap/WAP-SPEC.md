# WAP — Web App Progressive (Mobile-Optimized)

**المعرف:** `18-landing-wap`  
**النوع:** مواصفة تقنية ملزمة  
**الإصدار:** v1.0  
**الحالة:** 📝 مسودة — بانتظار الاعتماد  
**التبعية:** `I3-project-init-laravel`, `A1-register`

---

## 1. المقدمة والأهداف

### 1.1 ما هو WAP؟
WAP (Web App Progressive) هو تطبيق ويب خفيف محسّن للجوال للأدوار الثلاثة: **المستخدم** ⚡، **التاجر** 🏪، **الوكيل** 🏧. يعمل كـ PWA مع دعم جزئي لعدم الاتصال.

### 1.2 الأهداف
- ⚡ تحميل < 3s على شبكات 3G
- 📦 حجم الحزمة < 120KB (gzip) بعد التحميل الأولي
- 🔄 مزامنة تلقائية للمعاملات المؤجلة
- 🧭 فصل تام عن `frontend/admin/` — مشروع Next.js مستقل
- 🌐 دعم كامل للعربية (RTL) + الوضع الداكن

### 1.3 الجمهور المستهدف
| الدور | الوصف |
|-------|-------|
| ⚡ مستخدم | أفراد يملكون محافظ SYP/USD — يحولون، يدفعون، يفحصون رصيدهم |
| 🏪 تاجر | متاجر تقبل الدفع عبر Beza — ترى ملخص المبيعات، تنشئ QR |
| 🏧 وكيل | وكلاء إيداع/سحب نقدي — يديرون الحدود اليومية والعمولة |

---

## 2. الهيكلية التقنية

### 2.1 الإطار والتقنيات
| المكون | التقنية | الإصدار |
|--------|---------|---------|
| الإطار | Next.js (App Router) | 16.2.6+ |
| اللغة | TypeScript | 5.7+ |
| التصميم | Tailwind CSS | 4.x |
| الأيقونات | SVG Inline (react-icons/lucide) | — |
| الخط | Noto Sans Arabic (محلي) | — |
| PWA | next-pwa + Service Worker | latest |
| Offline DB | IndexedDB (idb wrapper) | — |
| المصادقة | HttpOnly Cookie (JWT) | — |

### 2.2 الفصل عن Admin
```
beza-platform/
├── frontend/
│   ├── admin/          # لوحة الإدارة (Next.js 16)
│   │   ├── package.json       # مستقل
│   │   └── next.config.ts     # مستقل
│   └── wap/            # تطبيق WAP (Next.js 16) ← 🆕
│       ├── package.json       # مستقل — package name: "beza-wap"
│       └── next.config.ts     # مستقل — basePath: "" , port: 3002
├── backend/            # Laravel 13 (API موحدة)
│   ├── routes/
│   │   ├── api.php           # مسارات Admin/App
│   │   └── api-wap.php       # مسارات WAP ← 🆕
│   └── ...
```

**قواعد الفصل:**
- `package.json` مختلف — اسم الحزمة `beza-wap`
- `next.config.ts` مختلف — `basePath: ''`, `port: 3002`
- `tsconfig.json` مستقل
- لا import مشترك بين `admin/` و `wap/`
- Design System مشترك عبر `packages/shared/` (اختياري مستقبلاً)
- API واحدة: `backend/` تخدم الطرفين

### 2.3 هيكل مسارات WAP
```
src/
├── app/
│   ├── page.tsx                ← redirect → /wap/login
│   ├── layout.tsx              ← layout جذر (RTL, fonts, meta)
│   ├── manifest.ts             ← PWA manifest
│   └── wap/
│       ├── layout.tsx          ← layout WAP (AuthGuard)
│       ├── login/
│       │   └── page.tsx        ← /wap/login
│       ├── dashboard/
│       │   └── page.tsx        ← /wap/dashboard (إعادة توجيه حسب الدور)
│       ├── user/
│       │   ├── page.tsx        ← /wap/user — الرصيد + التحويل السريع
│       │   ├── transfer/
│       │   │   └── page.tsx    ← /wap/user/transfer
│       │   ├── history/
│       │   │   └── page.tsx    ← /wap/user/history
│       │   └── qr/
│       │       └── page.tsx    ← /wap/user/qr (مسح QR)
│       ├── merchant/
│       │   ├── page.tsx        ← /wap/merchant — ملخص المبيعات
│       │   ├── qr/
│       │   │   └── page.tsx    ← /wap/merchant/qr (مولد QR)
│       │   └── settlements/
│       │       └── page.tsx    ← /wap/merchant/settlements
│       └── agent/
│           ├── page.tsx        ← /wap/agent — الحدود والعمولة
│           ├── pending/
│           │   └── page.tsx    ← /wap/agent/pending
│           └── queue/
│               └── page.tsx    ← /wap/agent/queue
├── lib/
│   ├── api/
│   │   ├── client.ts           ← عميل HTTP موحد (Cookie JWT)
│   │   ├── auth.ts             ← دوال المصادقة
│   │   └── endpoints.ts        ← ثوابت endpoints
│   ├── auth/
│   │   ├── session.ts          ← إدارة الجلسة
│   │   └── middleware.ts        ← AuthGuard middleware
│   ├── db/
│   │   ├── offline-queue.ts    ← IndexedDB Queue
│   │   └── schema.ts           ← IndexedDB schema
│   └── utils/
│       ├── format.ts           ← تنسيق العملات والأرقام
│       └── qr.ts               ← QR utilities
├── components/
│   ├── ui/                     ← مكونات مشتركة (Button, Card, Input...)
│   ├── offline-queue/          ← Offline Queue UI
│   ├── pwa/                    ← مكونات PWA (InstallPrompt, UpdateBanner)
│   └── layout/                 ← Header, BottomNav, Sidebar
├── hooks/
│   ├── useAuth.ts
│   ├── useBalance.ts
│   ├── useOfflineQueue.ts
│   └── usePWA.ts
├── public/
│   ├── fonts/                  ← Noto Sans Arabic (woff2)
│   ├── icons/                  ← SVG icons
│   ├── manifest.json
│   └── sw.js                   ← Service Worker
└── styles/
    └── globals.css             ← RTL + Dark mode + Tailwind
```

---

## 3. المصادقة — HttpOnly Cookie JWT

### 3.1 التدفق
```
[المتصفح]                    [Next.js Server]              [Laravel Backend]
    │                              │                             │
    │── POST /wap/api/auth/login ──→│                             │
    │                              │── POST /api/v1/wap/auth/login ──→│
    │                              │←── Set-Cookie: token=JWT... ────│
    │←── 302 → /wap/dashboard ─────│                             │
    │                              │   (HttpOnly; Secure; SameSite=Strict)
    │                              │                             │
    │── GET /wap/dashboard ────────→│                             │
    │                              │── GET /api/v1/wallet/balance ──→│
    │                              │   (Cookie: token=auto-sent)  │
    │←── { balance } ──────────────│                             │
```

### 3.2 كيف يعمل
1. المستخدم يسجل دخول → Next.js API Route (`/wap/api/auth/login`) تستقبل البيانات
2. Next.js Server Component يرسل طلب إلى Laravel API
3. Laravel يتحقق من البيانات → يصدر JWT → يضبط `Set-Cookie`
4. المتصفح يخزن الـ Cookie تلقائياً (HttpOnly — لا يمكن لمسه من JS)
5. كل طلب لاحق → الـ Cookie يُرسل تلقائياً مع الطلب
6. Laravel Middleware `ApiWapAuth` يتحقق من صحة JWT من الـ Cookie

### 3.3 إعدادات الـ Cookie
| الخاصية | القيمة | السبب |
|---------|--------|-------|
| `HttpOnly` | `true` | منع XSS من سرقة التوكن |
| `Secure` | `true` | HTTPS فقط (production) |
| `SameSite` | `Strict` | منع CSRF عبر المواقع الأخرى |
| `Path` | `/` | متاح لكل المسارات |
| `Max-Age` | `604800` (7 أيام) | مدة صلاحية الجلسة |
| `Domain` | (غير مضبوط) | النطاق الحالي فقط |

### 3.4 Refresh Token
- الـ JWT صالح لمدة 15 دقيقة
- الـ Refresh Token صالح لمدة 7 أيام (في ملف Cookie منفصل أو Same Cookie)
- `/wap/api/auth/refresh` → يستقبل Refresh Cookie → يصدر JWT جديد
- التحديث التلقائي يتم في Middleware أو API interceptor

### 3.5 الحماية
| الثغرة | الحماية |
|--------|---------|
| XSS | HttpOnly Cookie + CSP (Content-Security-Policy) |
| CSRF | SameSite=Strict + CSRF Token في headers |
| Token Theft | Secure فقط + مدة صلاحية قصيرة (15 د) |
| Replay Attack | JWT `jti` claim + Blacklist للـ JWT المستخدم |
| Logout | مسح Cookie + إبطال Refresh Token في Laravel |

---

## 4. نقاط API — `/api/v1/wap/`

### 4.1 المصادقة
| Method | المسار | الوصف | Auth |
|--------|--------|-------|------|
| POST | `/api/v1/wap/auth/login` | تسجيل دخول (بريد + كلمة سر) | ❌ |
| POST | `/api/v1/wap/auth/logout` | تسجيل خروج — إبطال التوكن | ✅ |
| POST | `/api/v1/wap/auth/refresh` | تحديث التوكن | ✅ (Refresh) |
| GET | `/api/v1/wap/auth/me` | معلومات المستخدم الحالي | ✅ |

### 4.2 المحفظة
| Method | المسار | الوصف | Auth |
|--------|--------|-------|------|
| GET | `/api/v1/wap/wallet/balance` | الرصيد (SYP + USD) — بصيغة `?format=minimal` | ✅ |
| GET | `/api/v1/wap/wallet/balance?format=minimal` | {balance, currency, updated_at} فقط | ✅ |

### 4.3 التحويلات
| Method | المسار | الوصف | Auth |
|--------|--------|-------|------|
| POST | `/api/v1/wap/wallet/transfer` | تحويل P2P (يدعم `idempotency_key`) | ✅ |
| POST | `/api/v1/wap/wallet/transfer/verify` | تأكيد تحويل مؤجل (من Offline Queue) | ✅ |

### 4.4 التاجر
| Method | المسار | الوصف | Auth |
|--------|--------|-------|------|
| GET | `/api/v1/wap/merchant/summary` | ملخص المبيعات اليومية | ✅ (Merchant) |
| GET | `/api/v1/wap/merchant/qr` | QR ديناميكي للمتجر (SVG/JSON) | ✅ (Merchant) |
| GET | `/api/v1/wap/merchant/settlements` | حالة التسوية المالية | ✅ (Merchant) |

### 4.5 الوكيل
| Method | المسار | الوصف | Auth |
|--------|--------|-------|------|
| GET | `/api/v1/wap/agent/limits` | حدود الإيداع/السحب اليومية | ✅ (Agent) |
| GET | `/api/v1/wap/agent/commissions` | عمولة اليوم | ✅ (Agent) |
| GET | `/api/v1/wap/agent/pending` | المعاملات المعلقة | ✅ (Agent) |

### 4.6 الردود الموحدة
```json
// نجاح
{ "success": true, "data": { ... }, "meta": { "timestamp": "..." } }

// خطأ
{ "success": false, "error": { "code": "VALIDATION_ERROR", "message": "..." } }

// مصادقة
{ "success": false, "error": { "code": "UNAUTHENTICATED", "message": "..." } }
```

### 4.7 Middleware — `ApiWapAuth`
- يستخرج JWT من `Cookie: token=...` (أولوية أولى) أو `Authorization: Bearer ...`
- يتحقق من توقيع JWT + صلاحيته
- يتحقق من `jti` في القائمة السوداء
- يضبط `auth()->setUser()` للـ request الحالي
- يدعم `?format=minimal` لتقليل حجم الرد

---

## 5. Offline Queue — IndexedDB + Background Sync

### 5.1 لماذا Offline Queue؟
- الشبكات الضعيفة في سوريا (3G, 4G غير مستقرة)
- المستخدم قد يرسل تحويلاً حين لا يوجد اتصال
- يجب حفظ الطلب وتنفيذه لاحقاً تلقائياً

### 5.2 هيكل IndexedDB
```
Database: beza_wap_offline
├── queue (object store)
│   ├── id (autoIncrement)
│   ├── method: string ("POST")
│   ├── endpoint: string ("/api/v1/wap/wallet/transfer")
│   ├── body: object (المحمول المرسل)
│   ├── idempotency_key: string (فريد — يمنع التكرار)
│   ├── status: "pending" | "processing" | "completed" | "failed"
│   ├── created_at: timestamp
│   ├── retry_count: number
│   └── last_error: string | null
├── cache (object store)
│   ├── key: string ("wallet:balance")
│   ├── value: object
│   └── expires_at: timestamp
```

### 5.3 التدفق
```
[إرسال تحويل]
    │
    ├─ هل الإنترنت متاح؟
    │   ├─ ✅ نعم → أرسل مباشرة → عرض النتيجة
    │   └─ ❌ لا → احفظ في IndexedDB (status: pending)
    │
    ├─ [مزامنة تلقائية]
    │   │
    │   ├─ عند عودة الاتصال:
    │   │   ├─ 1. اقرأ كل pending items
    │   │   ├─ 2. لكل item:
    │   │   │   ├─ أرسل مع idempotency_key
    │   │   │   ├─ نجاح → status: completed
    │   │   │   └─ فشل → retry_count++ (max 5)
    │   │   └─ 3. عرض إشعار للمستخدم
    │   │
    │   └─ Service Worker: fetch event
    │       ├─ إذا الطلب فشل (network error)
    │       └─ احفظ في IndexedDB ← Queue
    │
    └─ [Background Sync] (عند دعم المتصفح)
        └─ sync event ← معالجة الـ Queue
```

### 5.4 Idempotency
- كل طلب تحويل يحتوي `idempotency_key` (UUID v4)
- Laravel يتحقق من `idempotency_key` — إذا موجود، يعيد الرد السابق (لا ينفذ التحويل)
- يمنع التكرار عند إعادة الإرسال بعد انقطاع

### 5.5 واجهة المستخدم
```
┌──────────────────────────────┐
│  ⏳ معاملات معلقة (2)         │
│  ┌────────────────────────┐  │
│  │ 📤 تحويل 500 ل.س ← قيد │  │
│  │    التنفيذ...           │  │
│  └────────────────────────┘  │
│  ┌────────────────────────┐  │
│  │ ✅ تحويل 250 ل.س ← تم  │  │
│  └────────────────────────┘  │
└──────────────────────────────┘
```

---

## 6. Service Worker — PWA

### 6.1 ملفات SW
| الملف | الغرض |
|-------|-------|
| `public/sw.js` | Service Worker الرئيسي — توليد يدوي أو via next-pwa |
| `public/manifest.json` | PWA manifest — name, icons, theme_color |
| `src/app/manifest.ts` | Route handler للمانيفست ديناميكياً |

### 6.2 استراتيجية التخزين المؤقت
| المورد | الاستراتيجية | الشرح |
|--------|-------------|-------|
| الصفحات (HTML) | Network First | حاول الشبكة أولاً، ارجع للكاش إذا فشلت |
| CSS/JS (static) | Cache First | محملة مرة واحدة، تقدم من الكاش |
| أيقونات/صور | Cache First | حجم صغير، تخزين دائم |
| API responses (GET) | Network First + Cache | عرض الكاش إن فشلت الشبكة |
| API requests (POST) | Network Only | ترسل مباشرة (أو عبر Queue) |

### 6.3 Pre-cache (عند التثبيت)
```javascript
// install event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open('wap-v1').then((cache) => {
      return cache.addAll([
        '/',
        '/wap/login',
        '/wap/dashboard',
        '/wap/user',
        '/wap/merchant',
        '/wap/agent',
        '/fonts/NotoSansArabic.woff2',
        '/icons/icon-192.png',
        '/icons/icon-512.png',
      ]);
    })
  );
});
```

---

## 7. الأداء

### 7.1 المقاييس المستهدفة
| المقياس | الهدف |
|---------|-------|
| First Contentful Paint (FCP) | < 1.5s |
| Largest Contentful Paint (LCP) | < 2.5s |
| Time to Interactive (TTI) | < 3s |
| Lighthouse Performance | ≥ 90 |
| Lighthouse PWA | ≥ 90 |
| حجم الحزمة (gzip) | < 120KB |
| حجم الصفحة (HTML أولي) | < 50KB |

### 7.2 تقنيات التحسين
- **Code Splitting:** dynamic import للصفحات غير الأساسية
- **Lazy Loading:** للمكونات الثقيلة (QR scanner, charts)
- **Inline Critical CSS:** Tailwind CSS المطلوب للـ First View
- **Font Display:** `font-display: swap` + تحميل محلي
- **Image Optimization:** next/image مع WebP/AVIF
- **Preconnect:** لـ API domain
- **Minimize Response:** `?format=minimal` لردود API المصغرة

---

## 8. الأمان

### 8.1 معايير إلزامية
| المعيار | التطبيق |
|---------|---------|
| CSP | Content-Security-Policy صارم (no inline scripts except nonce) |
| HSTS | Strict-Transport-Security (max-age=31536000) |
| X-Content-Type-Options | nosniff |
| X-Frame-Options | DENY |
| Referrer-Policy | strict-origin-when-cross-origin |
| Permissions-Policy | camera(), geolocation() فقط عند الحاجة |

### 8.2 حماية API
- Rate Limiting: `50 req/min` لكل مستخدم
- Idempotency: `idempotency_key` للتحويلات
- Validation: Laravel Form Request مع قواعد صارمة
- WORM: لا تعديل/حذف للسجلات المالية
- Audit Log: كل طلب API يُسجل مع user_id, IP, timestamp

### 8.3 حماية المتصفح
- HttpOnly Cookie + SameSite=Strict + Secure
- CSP يمنع XSS
- لا تخزين sensitive data في localStorage/sessionStorage
- Sanitize كل الـ QR data المقروءة

---

## 9. خطة التنفيذ

| المرحلة | المدة | المخرجات |
|---------|-------|----------|
| 1. التوثيق | 1 يوم | WAP-SPEC.md + examples/18-landing-wap/ |
| 2. الباك إند | 2 يوم | routes/api-wap.php, Middleware, Controllers, Tests |
| 3. الواجهة | 3 يوم | frontend/wap/ (Next.js + PWA + Offline Queue) |
| 4. الربط والاختبار | 1 يوم | Playwright E2E + k6 Performance + تقرير |

### 9.1 التبعيات بين المراحل
```
Phase 1 (توثيق)
    │
    ▼
Phase 2 (باك إند) ─── يجب أن تمر اختبارات API أولاً ──→
    │                                                    │
    ▼                                                    ▼
Phase 3 (واجهة) ──── يتكامل مع API Phase 2 ─────→  Phase 4 (اختبار)
```

---

## 10. الموافقة

- [ ] تمت مراجعة المواصفات من 👑 CEO
- [ ] التصميم متوافق مع الدستور العام `CONSTITUTION.md`
- [ ] لا تعارض مع `frontend/admin/`
- [ ] جاهز للانتقال إلى **المرحلة 2: الباك إند**

---

> هذه الوثيقة هي المرجع الوحيد لتنفيذ WAP. أي انحراف عن المواصفات يتطلب ADR (Architecture Decision Record).
