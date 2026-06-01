# 06 - التجاوب (Responsive Layout)

## Bento Grid System

```
موبايل (< 640px)      تابلت (640-1023)      ديسكتوب (1024-1279)    كبير (1280+)
┌──────────┐          ┌──────┬──────┐        ┌──────┬──────┬──────┐  ┌──────┬──────┬──────┬──────┐
│          │          │      │      │        │      │      │      │  │      │      │      │      │
│  Wallet  │          │Wallet│Exch. │        │Wallet│Wallet│Exch. │  │Wallet│Wallet│Exch. │Exch. │
│          │          │      │      │        │      │      │      │  │      │      │      │      │
├──────────┤          ├──────┴──────┤        ├──────┴──────┼──────┤  ├──────┴──────┼──────┴──────┤
│          │          │             │        │             │      │  │             │             │
│ Exchange │          │   Actions   │        │   Actions   │Act.  │  │   Actions   │   Actions   │
│          │          │   2×2       │        │   2×2       │cont. │  │   2×2       │   2×2       │
├──────────┤          ├─────────────┤        ├─────────────┴──────┤  ├─────────────┴─────────────┤
│          │          │             │        │                    │  │                           │
│ Actions  │          │  Transact.  │        │   Transactions     │  │      Transactions         │
│  2×2     │          │             │        │                    │  │                           │
├──────────┤          └─────────────┘        └────────────────────┘  └───────────────────────────┘
│          │
│  Trans.  │
└──────────┘
```

## Breakpoints

| Breakpoint | العرض | الأعمدة | ملاحظة |
|------------|-------|---------|--------|
| `sm` | ≥ 640px | 2 | تابلت竖向 |
| `lg` | ≥ 1024px | 3 | ديسكتوب |
| `xl` | ≥ 1280px | 4 | شاشات كبيرة |

## توزيع الأعمدة حسب الـ Breakpoint

| المكون | sm | lg | xl |
|--------|----|----|----|
| KYC Alert | col-span-2 | col-span-3 | col-span-4 |
| Wallet | col-span-1 | col-span-2 | col-span-2 |
| Exchange | col-span-1 | col-span-1 | col-span-2 |
| Action Grid | col-span-2 | col-span-3 | col-span-4 |
| Transactions | col-span-2 | col-span-3 | col-span-4 |

## الهوامش والحشوات

```css
/* الصفحة */
min-h-dvh; py-6; px-4 sm:px-6 lg:px-8
/* الحاوية */
max-w-7xl mx-auto
/* الشبكة */
gap-5
/* البطاقات */
p-5 sm:p-6
/* الأزرار */
py-3.5; px-4
```
