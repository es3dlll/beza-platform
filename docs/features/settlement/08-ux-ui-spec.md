# Settlement UX/UI Specification

## Design Language
- **Role**: Back-office operations portal (web-based, not mobile)
- **Theme**: Light mode primarily (dark mode optional for monitoring)
- **Typography**: Arabic primary (Cairo, Noto Kufi Arabic), English secondary (Inter)
- **Direction**: RTL by default, LTR for data tables with mixed content
- **Icons**: Material Symbols with financial context

## Component Library

### Settlement Status Badge
```
| Status | Color | Icon | Description |
|--------|-------|------|-------------|
| DRAFT | gray/500 | draft | Batch created, not processed |
| PROCESSING | blue/500 | sync | Netting + payment generation in progress |
| AWAITING_CONFIRMATION | amber/500 | pending_actions | Payment sent, waiting for bank |
| ON_HOLD | red/500 | error | Exception detected, batch held |
| SETTLED | green/500 | check_circle | All items confirmed and reconciled |
| FAILED | red/700 | cancel | Batch failed (reprocess or abort) |
| PARTIALLY_SETTLED | orange/500 | warning | Some items settled, some pending |
```

### Data Table Components
```html
<!-- Settlement Batch Table -->
<table class="settlement-table">
  <thead>
    <tr>
      <th>رقم الدفعة</th>
      <th>النوع</th>
      <th>الشريك</th>
      <th>المعاملات</th>
      <th>القيمة</th>
      <th>الصافي</th>
      <th>الحالة</th>
      <th>الإجراءات</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>#20260529-001</td>
      <td>EOD</td>
      <td>Bemo Saudi Fransi</td>
      <td>8,400</td>
      <td>85,000,000</td>
      <td>+45,000,000</td>
      <td><span class="badge badge-processing">جاري</span></td>
      <td>
        <button>تفاصيل</button>
        <button>إلغاء</button>
      </td>
    </tr>
  </tbody>
</table>
```

### Exception Card
```html
<div class="exception-card" data-severity="high">
  <div class="exception-header">
    <span class="exception-id">EXC-20260529-001</span>
    <span class="exception-type">عدم تطابق المبلغ</span>
    <span class="exception-time">منذ ١٥ دقيقة</span>
  </div>
  <div class="exception-details">
    <div class="detail-row">
      <span class="label">داخلي:</span>
      <span class="value">500,000 ل.س</span>
    </div>
    <div class="detail-row">
      <span class="label">خارجي:</span>
      <span class="value">505,000 ل.س</span>
    </div>
    <div class="detail-row highlight">
      <span class="label">الفرق:</span>
      <span class="value">5,000 ل.س</span>
    </div>
  </div>
  <div class="exception-actions">
    <button class="btn-primary">تحقيق</button>
    <button class="btn-secondary">تجاوز</button>
  </div>
</div>
```

### Dashboard KPI Cards
```html
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-header">
      <span class="kpi-title">دفعات اليوم</span>
      <span class="kpi-icon">📦</span>
    </div>
    <div class="kpi-value">13</div>
    <div class="kpi-change positive">+2 عن الأمس</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header">
      <span class="kpi-title">نسبة المطابقة</span>
      <span class="kpi-icon">✅</span>
    </div>
    <div class="kpi-value">99.3%</div>
    <div class="kpi-change positive">+0.1%</div>
  </div>
  <div class="kpi-card danger">
    <div class="kpi-header">
      <span class="kpi-title">استثناءات نشطة</span>
      <span class="kpi-icon">⚠️</span>
    </div>
    <div class="kpi-value">3</div>
    <div class="kpi-change negative">+1 خلال ساعة</div>
  </div>
</div>
```

## Responsive Behavior
| Breakpoint | Layout | Notes |
|------------|--------|-------|
| >1440px | Full 3-column KPI, side nav | Optimal for operations desks |
| 1024-1440px | 2-column KPI, collapsed side nav | Standard laptop |
| 768-1024px | 1-column KPI, top nav | Tablet monitoring |
| <768px | Stacked, simplified table | Phone emergency view only |

## Empty States
```
🏗️ "لا توجد دفعات تسوية اليوم"
   → يبدأ التسوية التلقائية عند اكتمال دورة اليوم

🔍 "لا توجد استثناءات نشطة"
   → كل الأمور على ما يرام — نسبة المطابقة ١٠٠٪

📄 "لم يتم إنشاء تقارير بعد"
   → سيتم إنشاء التقرير اليومي تلقائياً الساعة ٢٣:٠٠
```
