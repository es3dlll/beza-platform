# UI Components

> Single source of truth for all reusable UI components across Beza Platform (Mobile & Web).

## BezaButton

### Props
```typescript
interface BezaButtonProps {
    variant: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
    size: 'sm' | 'md' | 'lg';
    fullWidth?: boolean;
    loading?: boolean;
    disabled?: boolean;
    icon?: IconName;          // Leading icon
    iconPosition?: 'left' | 'right';
    children: ReactNode;
    onClick?: () => void;
    type?: 'button' | 'submit';
}
```

### Variants
| Variant | Background | Text | Border | Use Case |
|---------|-----------|------|--------|----------|
| `primary` | `#1B5E20` | `#FFFFFF` | None | Primary CTAs: Send, Confirm, Pay |
| `secondary` | `#2E7D32` | `#FFFFFF` | None | Secondary actions: Top Up, Add |
| `outline` | Transparent | `#1B5E20` | `#1B5E20` 2px | Tertiary actions: Cancel, Skip |
| `ghost` | Transparent | `#1B5E20` | None | Text buttons: Learn More, Details |
| `danger` | `#FF3B30` | `#FFFFFF` | None | Destructive: Delete, Block |

### States

**Default** — Solid background, full opacity
**Hover** — Background darkens by 10% (filter: brightness(0.9))
**Pressed** — Background darkens by 15%, scale 0.97 (50ms animation)
**Loading** — Show spinner icon, text hidden, pointer-events: none
**Disabled** — Opacity 0.38, pointer-events: none

### Usage
```tsx
<BezaButton variant="primary" size="lg" fullWidth onClick={handleSend}>
    إرسال
</BezaButton>

<BezaButton variant="outline" size="sm" icon="arrow-left" iconPosition="right">
    رجوع
</BezaButton>

<BezaButton variant="danger" loading={isDeleting} disabled={!canDelete}>
    حذف
</BezaButton>
```

### Sizing
| Size | Height | Padding (X) | Font Size | Icon Size |
|------|--------|-------------|-----------|-----------|
| `sm` | 36px | 16px | 14px | 16px |
| `md` | 44px | 24px | 16px | 20px |
| `lg` | 52px | 32px | 18px | 24px |

---

## BezaInput

### Props
```typescript
interface BezaInputProps {
    type: 'text' | 'phone' | 'pin' | 'amount' | 'search' | 'textarea';
    label?: string;
    placeholder?: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;            // Arabic error message
    hint?: string;             // Helper text below input
    disabled?: boolean;
    readonly?: boolean;
    maxLength?: number;
    autoFocus?: boolean;
    prefix?: string;           // e.g. "+963" for phone
    suffix?: string;           // e.g. "ل.س" for amount
    dir?: 'rtl' | 'ltr';
}
```

### Types
| Type | Keyboard | Formatting | Validation |
|------|----------|------------|------------|
| `text` | Default | None | None |
| `phone` | Phone numeric | Auto-format +963 XXX XXX XXX | Regex: `/^\+9639\d{8}$/` |
| `pin` | Numeric (secure) | Masked `••••` | Length 4-6 digits |
| `amount` | Numeric decimal | Arabic-Indic digits, `ل.س` suffix | Min 100, Max 10,000,000 |
| `search` | Default | Clear button when has value | Debounce 300ms |
| `textarea` | Multiline | Auto-resize, char counter | Max 500 chars |

### States

**Default** — Border `#C7C7CC`, bg `#FFFFFF`
**Focused** — Border `#1B5E20`, box-shadow green glow
**Filled** — Border `#C7C7CC`, value present
**Error** — Border `#FF3B30`, error text below, shake animation
**Disabled** — bg `#F2F2F7`, opacity 0.5
**Success** — Border `#34C759`, optional checkmark icon

### Usage
```tsx
<BezaInput
    type="phone"
    label="رقم الهاتف"
    placeholder="944XXXXXX"
    prefix="+963"
    value={phone}
    onChange={setPhone}
    error="رقم الهاتف غير صالح"
/>

<BezaInput
    type="amount"
    label="المبلغ"
    suffix="ل.س"
    value={amount}
    onChange={setAmount}
    hint="الحد الأدنى ١٠٠ ل.س"
/>

<BezaInput
    type="pin"
    label="رمز PIN"
    maxLength={4}
    value={pin}
    onChange={setPin}
/>
```

---

## BezaCard

### Props
```typescript
interface BezaCardProps {
    variant: 'elevated' | 'outlined' | 'flat';
    padding?: 'sm' | 'md' | 'lg';
    onClick?: () => void;
    children: ReactNode;
}
```

### Variants
| Variant | Shadow | Border | Use Case |
|---------|--------|--------|----------|
| `elevated` | `0 2px 8px rgba(0,0,0,0.12)` | None | Primary content cards |
| `outlined` | None | `1px solid #C7C7CC` | Secondary, settings |
| `flat` | None | None | Nested within cards |

### Padding
| Size | Padding |
|------|---------|
| `sm` | 12px |
| `md` | 16px |
| `lg` | 24px |

### Usage
```tsx
<BezaCard variant="elevated" padding="md" onClick={openDetails}>
    <BezaCardHeader title="محفظتي" subtitle="SYF ١٬٢٠٤٬٥٠٠ ل.س" />
    <BezaCardContent>
        <p>آخر حركة: ٢٩ مايو ٢٠٢٥</p>
    </BezaCardContent>
</BezaCard>
```

---

## BezaBottomSheet

### Props
```typescript
interface BezaBottomSheetProps {
    isOpen: boolean;
    onClose: () => void;
    snapPoints?: ('25%' | '50%' | '75%' | '90%')[];
    initialSnap?: number;      // Index into snapPoints
    showDragHandle?: boolean;
    children: ReactNode;
}
```

### Behavior
- Opens with slide-up animation (300ms, ease-out)
- Drag handle at top for dismiss
- Overlay background (`rgba(0,0,0,0.4)`) behind sheet
- Closes on swipe down past 40% threshold
- Closes on overlay tap
- Content scrollable internally when sheet expanded beyond content

### Usage
```tsx
<BezaBottomSheet isOpen={showOptions} onClose={hideOptions} snapPoints={['50%', '90%']}>
    <div style={{ padding: 16 }}>
        <h3>خيارات التحويل</h3>
        <BezaButton variant="primary" fullWidth>تحويل الآن</BezaButton>
        <BezaButton variant="outline" fullWidth>جدولة لاحقاً</BezaButton>
    </div>
</BezaBottomSheet>
```

---

## BezaModal

### Props
```typescript
interface BezaModalProps {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    variant: 'alert' | 'confirm' | 'info' | 'success' | 'error';
    confirmText?: string;
    cancelText?: string;
    onConfirm?: () => void;
    onCancel?: () => void;
    children?: ReactNode;
}
```

### Variants
| Variant | Icon | Confirm Button |
|---------|------|----------------|
| `alert` | Warning triangle | Danger variant |
| `confirm` | Question mark | Primary variant |
| `info` | Info circle | Primary variant |
| `success` | Checkmark circle | Primary variant |
| `error` | X circle | Primary variant |

### Behavior
- Fade-in overlay (200ms)
- Scale-up content (200ms, ease-out, from 0.9 → 1.0)
- Close on overlay tap (unless variant=alert)
- Close on Escape key (web)
- Focus trap inside modal
- Scroll lock on body behind

### Usage
```tsx
<BezaModal
    isOpen={showConfirm}
    onClose={hideConfirm}
    variant="confirm"
    title="تأكيد التحويل"
    description="هل أنت متأكد من تحويل ٥٠٬٠٠٠ ل.س إلى أحمد؟"
    confirmText="تأكيد"
    cancelText="إلغاء"
    onConfirm={handleConfirm}
    onCancel={hideConfirm}
/>
```

---

## BezaBadge

### Props
```typescript
interface BezaBadgeProps {
    variant: 'success' | 'warning' | 'error' | 'info' | 'neutral';
    size: 'sm' | 'md';
    children: ReactNode;
    dot?: boolean;             // Small dot indicator only
    removable?: boolean;       // Shows X button
    onRemove?: () => void;
}
```

### Variants
| Variant | Background | Text | Use Case |
|---------|-----------|------|----------|
| `success` | `#34C759` 15% opacity | `#34C759` | Active, Completed |
| `warning` | `#FF9500` 15% opacity | `#FF9500` | Pending, Suspended |
| `error` | `#FF3B30` 15% opacity | `#FF3B30` | Failed, Blocked |
| `info` | `#007AFF` 15% opacity | `#007AFF` | Info, New |
| `neutral` | `#C7C7CC` 30% opacity | `#3A3A3C` | Inactive, Draft |

### Usage
```tsx
<BezaBadge variant="success" size="sm">تمت</BezaBadge>
<BezaBadge variant="warning" dot>قيد المراجعة</BezaBadge>
<BezaBadge variant="info" removable onRemove={dismiss}>جديد</BezaBadge>
```

---

## BezaSkeleton

### Props
```typescript
interface BezaSkeletonProps {
    variant: 'text' | 'circle' | 'rect' | 'card';
    width?: string | number;
    height?: string | number;
    count?: number;            // Repeat skeleton N times
    borderRadius?: number;
}
```

### Animation
- Shimmer sweep: gradient animation moving left to right (1500ms loop)
- Base color: `#F2F2F7`
- Shimmer color: `#E5E5EA`

### Usage
```tsx
// Loading a list of 3 cards
<BezaSkeleton variant="card" count={3} />

// Loading a text block
<BezaSkeleton variant="text" width="80%" />
<BezaSkeleton variant="text" width="60%" />

// Avatar loading
<BezaSkeleton variant="circle" width={48} height={48} />
```

---

## BezaEmptyState

### Props
```typescript
interface BezaEmptyStateProps {
    icon: IconName;
    title: string;
    description: string;
    actionText?: string;
    onAction?: () => void;
}
```

### Usage
```tsx
<BezaEmptyState
    icon="inbox"
    title="لا توجد معاملات"
    description="لم تقم بأي تحويلات بعد. ابدأ بتحويل أموالك الآن!"
    actionText="تحويل الآن"
    onAction={startTransfer}
/>
```

---

## BezaErrorState

### Props
```typescript
interface BezaErrorStateProps {
    icon?: IconName;           // Default: alert-circle
    title: string;
    description: string;
    retryText?: string;
    onRetry?: () => void;
    supportText?: string;
    onContactSupport?: () => void;
}
```

### Usage
```tsx
<BezaErrorState
    title="حدث خطأ"
    description="تعذر تحميل المعاملات. يرجى المحاولة مرة أخرى."
    retryText="إعادة المحاولة"
    onRetry={fetchTransactions}
    supportText="تواصل مع الدعم"
    onContactSupport={openSupport}
/>
```
