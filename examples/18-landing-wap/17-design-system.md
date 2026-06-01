# نظام التصميم المشترك — Admin + WAP

## المبدأ
WAP و Admin يشتركان في نفس **Design Tokens** (ألوان، خطوط، مسافات) لكن لكل منهما **مكوناته المستقلة**.

## Design Tokens (CSS Variables)
```css
:root {
  /* الألوان الأساسية */
  --color-primary: #3b82f6;
  --color-primary-dark: #2563eb;
  --color-success: #22c55e;
  --color-warning: #eab308;
  --color-error: #ef4444;

  /* الخلفيات */
  --bg-primary: #ffffff;
  --bg-secondary: #f8fafc;
  --bg-dark: #0f172a;

  /* الخطوط */
  --font-sans: 'Noto Sans Arabic', sans-serif;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;

  /* المسافات */
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
}
```

## مكتبة مشتركة (مستقبلية — post-MVP)
```
packages/shared/           ← غير إلزامي الآن
├── tokens.css             ← Design Tokens
├── types/                 ← TypeScript types مشتركة
│   ├── user.ts
│   ├── wallet.ts
│   └── transaction.ts
└── utils/
    ├── format.ts          ← تنسيق العملات
    └── validation.ts      ← قواعد التحقق
```

## المكونات
WAP لا يستورد مكونات من Admin مباشرة. كل مشروع له مكوناته الخاصة لكن بنفس النمط البصري. يمكن توحيد المكونات مستقبلاً عبر `packages/shared/ui/`.

> **قاعدة:** في MVP، المكونات منفصلة لكن الألوان والخطوط موحدة. بعد MVP، `packages/shared/` يوحّد المكونات.
