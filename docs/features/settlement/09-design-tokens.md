# Settlement Design Tokens

## Color Palette

### Status Colors
```json
{
  "settlement": {
    "status": {
      "draft": { "bg": "#F3F4F6", "text": "#374151", "border": "#D1D5DB" },
      "processing": { "bg": "#EFF6FF", "text": "#1D4ED8", "border": "#93C5FD" },
      "awaitingConfirmation": { "bg": "#FFFBEB", "text": "#B45309", "border": "#FCD34D" },
      "onHold": { "bg": "#FEF2F2", "text": "#B91C1C", "border": "#FCA5A5" },
      "settled": { "bg": "#F0FDF4", "text": "#15803D", "border": "#86EFAC" },
      "failed": { "bg": "#FEF2F2", "text": "#991B1B", "border": "#F87171" },
      "partiallySettled": { "bg": "#FFF7ED", "text": "#C2410C", "border": "#FDBA74" }
    },
    "severity": {
      "critical": "#DC2626",
      "high": "#EA580C",
      "medium": "#CA8A04",
      "low": "#16A34A"
    },
    "entity": {
      "bank": "#3B82F6",
      "biller": "#8B5CF6",
      "merchant": "#10B981",
      "agent": "#F59E0B",
      "internal": "#6B7280"
    }
  }
}
```

### Typography
```css
--font-family-arabic: 'Cairo', 'Noto Kufi Arabic', sans-serif;
--font-family-english: 'Inter', 'Segoe UI', sans-serif;
--font-family-mono: 'JetBrains Mono', 'Cascadia Code', monospace;

--font-size-xs: 0.75rem;    /* 12px — table cells */
--font-size-sm: 0.875rem;   /* 14px — body */
--font-size-base: 1rem;     /* 16px — labels */
--font-size-lg: 1.125rem;   /* 18px — card titles */
--font-size-xl: 1.25rem;    /* 20px — section headers */
--font-size-2xl: 1.5rem;    /* 24px — KPI values */
--font-size-3xl: 2rem;      /* 32px — hero numbers */

--font-weight-regular: 400;
--font-weight-medium: 500;
--font-weight-semibold: 600;
--font-weight-bold: 700;
```

### Spacing
```css
--spacing-xs: 4px;
--spacing-sm: 8px;
--spacing-md: 16px;
--spacing-lg: 24px;
--spacing-xl: 32px;
--spacing-2xl: 48px;
```

### Component Tokens
```css
/* Card */
--card-bg: #FFFFFF;
--card-border: #E5E7EB;
--card-radius: 8px;
--card-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

/* Table */
--table-header-bg: #F9FAFB;
--table-row-hover: #F3F4F6;
--table-row-border: #E5E7EB;
--table-radius: 6px;

/* Badge */
--badge-radius: 9999px;
--badge-padding-x: 8px;
--badge-padding-y: 2px;

/* Button */
--btn-primary-bg: #1D4ED8;
--btn-primary-hover: #1E40AF;
--btn-primary-text: #FFFFFF;
--btn-secondary-bg: #FFFFFF;
--btn-secondary-border: #D1D5DB;
--btn-danger-bg: #DC2626;
--btn-danger-hover: #B91C1C;
```

### Animation Tokens
```css
--transition-fast: 150ms ease;
--transition-normal: 250ms ease;
--transition-slow: 350ms ease;
```
