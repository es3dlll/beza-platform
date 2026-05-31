# Design System — Marketplace Components

## Atoms

### Button — Primary
```css
.btn-primary {
  background: var(--marketplace-primary); /* #1A73E8 */
  color: #FFFFFF;
  border-radius: 12px;
  padding: 14px 24px;
  font-size: 14px;
  font-weight: 500;
  width: 100%;
  transition: opacity 0.2s;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
```

### Button — Secondary
```css
.btn-secondary {
  background: #FFFFFF;
  color: var(--marketplace-primary);
  border: 1.5px solid var(--marketplace-primary);
  border-radius: 12px;
  padding: 14px 24px;
  font-size: 14px;
  font-weight: 500;
  width: 100%;
}
```

### Chip
```css
.chip {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  border: 1px solid var(--marketplace-border);
  background: var(--marketplace-card-bg);
  cursor: pointer;
}
.chip--selected {
  background: var(--marketplace-primary);
  color: #FFFFFF;
  border-color: var(--marketplace-primary);
}
.chip--amount {
  min-width: 64px;
  justify-content: center;
  font-weight: 600;
}
```

### Skeleton Loader
```css
.skeleton {
  background: linear-gradient(90deg, #E5E7EB 25%, #F3F4F6 50%, #E5E7EB 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 8px;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

## Molecules

### Product Card
```
<div class="product-card">
  <div class="product-card__image">
    <img src="..." alt="..." />
    <span class="product-card__badge">وفر ٢٠٪</span>
  </div>
  <div class="product-card__body">
    <h3 class="product-card__title">اسم المنتج</h3>
    <div class="product-card__meta">
      <span class="product-card__rating">⭐ 4.8</span>
      <span class="product-card__vendor">المتجر</span>
    </div>
    <div class="product-card__footer">
      <span class="product-card__price">٢٨,٠٠٠ ل.س</span>
      <button class="btn-add-cart">🛒</button>
    </div>
  </div>
</div>
```

### Category Tile
```
<a class="category-tile" href="/marketplace/category/gift-cards">
  <div class="category-tile__icon">🎁</div>
  <span class="category-tile__label">بطاقات هدايا</span>
</a>
```
- 3 columns per row on mobile
- 140x100px fixed size
- Icon 32px, label 12px below

### Top-Up Confirmation Sheet
```
<div class="bottom-sheet">
  <div class="bottom-sheet__handle"></div>
  <div class="bottom-sheet__content">
    <div class="summary-row">
      <span>الشبكة</span>
      <span>سيريتل</span>
    </div>
    <div class="summary-row">
      <span>الرقم</span>
      <span dir="ltr">0933 456 789</span>
    </div>
    <div class="summary-row">
      <span>المبلغ</span>
      <span>١٠,٠٠٠ ل.س</span>
    </div>
    <div class="summary-row">
      <span>الرسوم</span>
      <span>٠ ل.س</span>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-row summary-row--total">
      <span>الإجمالي</span>
      <span>١٠,٠٠٠ ل.س</span>
    </div>
    <div class="summary-row">
      <span>رصيد المحفظة</span>
      <span class="text-green">٤٥,٣٠٠ ل.س</span>
    </div>
    <button class="btn-primary">تأكيد الشحن</button>
  </div>
</div>
```

## Organisms

### Marketplace Header
```
<header class="mkt-header">
  <div class="mkt-header__left">
    <h1>السوق</h1>
    <span class="wallet-badge">٤٥,٣٠٠ ل.س</span>
  </div>
  <div class="mkt-header__right">
    <button class="icon-btn">🔔</button>
    <button class="icon-btn cart-btn">
      🛒
      <span class="cart-badge">٣</span>
    </button>
  </div>
</header>
```

### Order Detail Card
```
<div class="order-card">
  <div class="order-card__header">
    <span class="order-id">#MKT-2026-05189</span>
    <span class="order-status order-status--delivered">تم التوصيل</span>
  </div>
  <div class="order-card__items">
    <div class="order-item">
      <span class="order-item__name">شحن سيريتل ١٠,٠٠٠ ل.س</span>
      <span class="order-item__qty">×١</span>
    </div>
    <div class="order-item">
      <span class="order-item__name">PUBG 600 UC</span>
      <span class="order-item__qty">×١</span>
    </div>
  </div>
  <div class="order-card__footer">
    <span class="order-card__total">٣٨,٠٠٠ ل.س</span>
    <div class="order-card__actions">
      <button class="btn-text">إعادة الطلب</button>
      <button class="btn-text">تقييم</button>
    </div>
  </div>
</div>
```

## Templates

### Marketplace Home Template
```
┌─────────────────────────────────┐
│ MarketplaceHeader               │
├─────────────────────────────────┤
│ SearchBar                       │
├─────────────────────────────────┤
│ CategoryGrid (3×2)              │
├─────────────────────────────────┤
│ HorizontalScroll "عروض خاصة"    │
├─────────────────────────────────┤
│ ProductGrid (2 cols) "الأكثر    │
│  مبيعاً"                         │
└─────────────────────────────────┘
```

### Product Listing Template
```
┌─────────────────────────────────┐
│ Header: "سلع رقمية"             │
├─────────────────────────────────┤
│ SearchBar + FilterChips         │
├─────────────────────────────────┤
│ ProductCard (list) × N          │
│                                 │
│ LoadMore / Pagination           │
└─────────────────────────────────┘
```

## Animation Specs

| Element | Animation | Duration | Easing |
|---|---|---|---|
| Page transition | Slide left/right | 300ms | ease-in-out |
| Add to cart | Icon scale + badge increment | 200ms | ease-out |
| Success toast | Slide down + fade | 300ms in, 3s display, 300ms out | ease-in-out |
| Bottom sheet | Slide up | 350ms | cubic-bezier(0.16, 1, 0.3, 1) |
| Skeleton shimmer | Translate gradient | 1.5s loop | linear |
| Product card tap | Scale 0.97 | 100ms | ease-out |
