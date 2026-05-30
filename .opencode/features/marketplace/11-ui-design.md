# UI Design

## Design Principles

1. **Arabic-first**: RTL layout as default, LTR only when English is selected
2. **Trust signals**: Verified badges, vendor ratings, secure payment indicators throughout
3. **Speed**: Minimalist screens with fast transitions; skeleton loaders over spinners
4. **Accessibility**: Large touch targets (min 48px), high contrast, clear typography hierarchy
5. **Consistency**: Shared component library with Beza design system

## Color Palette

| Token | Hex | Usage |
|---|---|---|
| --marketplace-primary | #1A73E8 | Primary buttons, links, active states |
| --marketplace-secondary | #34A853 | Success, delivered, green checkmarks |
| --marketplace-accent | #FF6D00 | Promotions, discounts, hot deals |
| --marketplace-warning | #EA4335 | Errors, insufficient balance, cancellations |
| --marketplace-bg | #F5F7FA | Page background |
| --marketplace-card-bg | #FFFFFF | Card surfaces |
| --marketplace-text-primary | #1F2937 | Primary text |
| --marketplace-text-secondary | #6B7280 | Secondary text, hints |
| --marketplace-border | #E5E7EB | Card borders, dividers |

## Typography

| Element | Font | Size | Weight |
|---|---|---|---|
| Heading 1 | Noto Sans Arabic | 24px | Bold (700) |
| Heading 2 | Noto Sans Arabic | 20px | Bold (700) |
| Heading 3 | Noto Sans Arabic | 16px | Semi-Bold (600) |
| Body | Noto Sans Arabic | 14px | Regular (400) |
| Caption | Noto Sans Arabic | 12px | Regular (400) |
| Price | Noto Sans Arabic | 18px | Bold (700) |
| Button | Noto Sans Arabic | 14px | Medium (500) |

## Key Components

### Product Card
```
┌─────────────────┐
│ [Product Image] │ 160x160px
├─────────────────┤
│ اسم المنتج       │
│ ٢٨,٠٠٠ ل.س     │
│ ⭐ 4.8  |  متجر  │
│ [🛒 أضف إلى السلة]│
└─────────────────┘
```

### Top-Up Amount Picker
```
┌───┬───┬───┬───┬───┬───┐
│٢٥٠│٥٠٠│١٠٠٠|٢٥٠٠|٥٠٠٠│١٠آلاف│
│   │   │ [●]│    │    │      │
└───┴───┴───┴───┴───┴───┘
```
Selected chip: primary bg, white text
Unselected: light gray bg, primary text

### Order Status Badge
| Status | Badge Color | Text |
|---|---|---|
| Pending | Yellow | قيد الانتظار |
| Processing | Blue | قيد المعالجة |
| Delivered | Green | تم التوصيل |
| Cancelled | Red | ملغي |
| Refunded | Gray | مسترجع |

## Screen Layout

### Marketplace Home
- **Header**: Title "السوق", wallet balance, notifications icon, cart icon
- **Search Bar**: Full width with placeholder text
- **Category Grid**: 3 columns, 2 rows, icon + label
- **Offers**: Horizontal scrollable cards (rounded, shadow)
- **Bestsellers**: Grid of product cards (2 columns)

### Mobile Top-Up Screen
- **Step indicator**: 1. Network → 2. Number → 3. Amount → 4. Confirm
- **Network selection**: Two large tappable cards (Syriatel / MTN) with logos
- **Number input**: Single input field with keyboard; paste support
- **Favorites**: Horizontal scrollable chips with saved names
- **Amount picker**: Chips grid; custom amount at bottom
- **Confirm section**: Sticky bottom sheet with summary + action button

## Responsive Behavior

- **Mobile (360–480px)**: Single column, bottom sheet confirmations
- **Tablet (768–1024px)**: Two-column product grid, side panel cart
- **Desktop (1280px+)**: Max content width 1200px, left nav for categories

## Empty States

| State | Message | Action |
|---|---|---|
| No search results | "لا توجد نتائج للبحث" | Clear filters / browse categories |
| Empty cart | "سلتك فارغة" | "تسوق الآن" button |
| No orders | "لم تقم بأي طلب بعد" | "استعرض المنتجات" button |
| No gift cards | "لا تملك أي بطاقة هدية" | "اشتر بطاقة هدية" button |
| No saved numbers | "لا توجد أرقام محفوظة" | "احفظ رقم" button |
