# 02 - عمارة موقع Beza التسويقي

## نظرة عامة

```
┌─────────────────────────────────────────────────────┐
│                   CDN (Vercel Edge)                  │
├─────────────────────────────────────────────────────┤
│                 Next.js SSG App                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐   │
│  │  Pages   │  │Comps     │  │  Layouts         │   │
│  │  - Home  │  │- Hero    │  │  - Header        │   │
│  │  - merch │  │- Features│  │  - Footer        │   │
│  │  - agents│  │- Testimon│  │  - SEO Head      │   │
│  │  - downld│  │- Pricing │  │                  │   │
│  │          │  │- FAQ     │  │                  │   │
│  └──────────┘  └──────────┘  └──────────────────┘   │
├─────────────────────────────────────────────────────┤
│              API Layer (Laravel)                     │
│  ┌──────────────────────────────────────────────┐   │
│  │  - POST /api/contact                         │   │
│  │  - POST /api/newsletter/subscribe            │   │
│  │  - POST /api/merchant-inquiry                │   │
│  │  - POST /api/agent-inquiry                   │   │
│  └──────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────┤
│                  Database (MySQL)                    │
│  ┌──────────────────────────────────────────────┐   │
│  │  contacts | subscribers | inquiries          │   │
│  └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

## لماذا Next.js SSG؟

| الميزة | SSG | SSR | CSR |
|--------|-----|-----|-----|
| سرعة التحميل | ⚡ فوري (HTML جاهز) | أبطأ (يُولد لكل طلب) | أبطأ (JS يبني الصفحة) |
| SEO | ✅ ممتاز | ✅ جيد | ❌ ضعيف |
| تكلفة الاستضافة | مجاني (Vercel) | يحتاج خادم | يحتاج خادم |
| تفاعل المستخدم | مع JS | مع JS | مع JS |
| المحتوى | ثابت (يُبنى عند النشر) | ديناميكي | ديناميكي |

## مكونات الصفحة الرئيسية

```
Page Layout
├── Header (SEO + Navigation)
│   ├── Logo
│   ├── Nav Links (المميزات، التجار، التحميل)
│   └── CTA Buttons (تحميل التطبيق، فتح متجر)
├── Hero Section
│   ├── Headline + Subheadline
│   ├── CTA Buttons
│   └── Hero Image / Mockup
├── Features Section
│   ├── محفظة رقمية
│   ├── بطاقات مسبقة الدفع
│   ├── صفقات وعروض
│   └── وكلاء صرافة
├── Testimonials
│   ├── User Reviews (Carousel)
│   └── Rating Stars
├── Pricing Section
│   ├── Free Plan
│   ├── Merchant Plan
│   └── Agent Plan
├── FAQ Section
│   ├── Accordion Items
│   └── Search (اختياري)
└── Footer
    ├── Links
    ├── Social Media
    └── Copyright
```

## SEO Strategy

```typescript
// next.config.js
module.exports = {
  output: 'export', // SSG
  images: { unoptimized: true },
  i18n: {
    locales: ['ar'],
    defaultLocale: 'ar',
  },
};
```

```typescript
// لكل صفحة — generateMetadata
export const metadata: Metadata = {
  title: 'Beza | المنصة الرقمية للمدفوعات',
  description: 'حوّل أموالك بسهولة وأمان مع Beza',
  openGraph: {
    title: 'Beza - الدفع الرقمي في سوريا',
    description: 'محفظة رقمية، بطاقات مسبقة الدفع، تحويلات فورية',
    images: ['https://beza.com/og-image.png'],
  },
  twitter: {
    card: 'summary_large_image',
  },
};
```
