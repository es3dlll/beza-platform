# مبادئ التصميم — UI/UX Design Principles

## Design Language
- **Direction**: Right-to-Left (RTL) as default
- **Colors**: Beza brand palette — Green (ثقة), White (نقاء), Gold (بركة)
- **Typography**: Arabic font (Cairo or Tajawal) for Arabic, Inter for English/SYLP
- **Tone**: Trustworthy, warm, professional — financial products require confidence

## Key Principles
1. **Trust First** — Every screen shows Sharia compliance badge, clear fee disclosure, no hidden terms
2. **Progress Visibility** — Application progress bar (4 steps: Submit → Score → Offer → Disburse)
3. **Education Integrated** — Each product card explains "How it works" in simple Arabic
4. **Default Choices** — Smart defaults (amount, term) based on user profile
5. **Confirmation Steps** — Every financial action requires explicit confirmation (double-tap for large amounts)

## Home Screen Integration
```
+------------------------------------------+
|  مرحباً بك يا ليلى                        |
|  رصيد المحفظة: ١,٢٥٠,٠٠٠ ل.س            |
+------------------------------------------+
|  التمويل (Financing)                       |
|  +--------------------------------------+ |
|  | قرض حسن      | مرابحة    | تمويل     | |
|  | حد: ٥٠٠ ألف  | حد: ٥ ملايين| حد: ١٠  | |
|  | %٠ فائدة      | ربح: ٥–١٢%  | ملايين    | |
|  +--------------------------------------+ |
|  [تقدم بطلب]                               |
+------------------------------------------+
|  قرضي النشط: ٢٠٠,٠٠٠ ل.س                 |
|  الدفعة القادمة: ١٠ يونيو                 |
|  [سدد الآن]                                |
+------------------------------------------+
|  درجة الائتمان: ٦٨٠ ▲                      |
|  [عرض التفاصيل]                           |
+------------------------------------------+
```

## Screens Overview

### 1. Financing Hub (الصفحة الرئيسية للتمويل)
- Product cards with visual icons
- Active loan summary card
- Quick actions (Apply, Pay, View Schedule)
- Credit score display with trend

### 2. Application Form (نموذج الطلب)
- Product selector (auto-highlights eligible products)
- Amount slider (min–max with visual markers)
- Term selector (days or months)
- Purpose dropdown (medical, education, business, etc.)
- Document upload (camera integration for ID/invoices)
- Guarantor selector (Qard Hasan only)

### 3. Application Status (حالة الطلب)
- Timeline visualization (submitted → underwriting → approved → disbursed)
- Animated processing indicator
- Estimated time remaining
- Status updates in real-time

### 4. Offer Screen (عرض التمويل)
- Clear table: Amount, Profit, Total, Installment
- "تفاصيل العقد" expandable section with full terms
- "قبول" (Accept) and "رفض" (Reject) buttons
- Countdown timer (offer expiry)

### 5. Active Loan Detail (تفاصيل التمويل النشط)
- Progress bar (paid vs remaining)
- Next payment: Amount, Due date, Days remaining
- Payment history table
- "سدد مبكراً" (Early repayment) button
- "طلب إعادة جدولة" (Restructure request) link

### 6. Credit Score Dashboard (لوحة درجة الائتمان)
- Score gauge (300–850)
- Factor breakdown (pie chart or horizontal bars)
- "How to improve" list
- Score history chart (3 months / 6 months / 1 year)

### 7. Admin Dashboard (لوحة المشرف)
- Portfolio summary (disbursed, active, delinquent)
- Application queue (filter: pending, underwriting)
- User search
- Collection management view
- Reports section

## Error States
- **No Internet**: "يرجى التحقق من اتصال الإنترنت" (Check internet connection)
- **Insufficient Balance for Auto-Deduct**: "الرصيد غير كافٍ، يرجى إيداع المبلغ" (Insufficient balance, please deposit)
- **Application Rejected**: Clear reason + improvement suggestions + next eligible date
- **Payment Failed**: Error code + support contact + retry button
