# 25 — Localisation & Arabic-First

## 25.1 Language Strategy

| Role | Default UI | Secondary | Notes |
|---|---|---|---|
| Parent app | Arabic | English | Arabic-only for most parents; English optional in settings |
| School dashboard | Arabic | English | Admin may prefer English; Arabic default |
| Receipts | Arabic | English | Arabic mandatory by law |
| SMS reminders | Arabic | — | Only Arabic (Syrian parents) |
| WhatsApp messages | Arabic | English | Based on recipient preference |
| API error messages | Arabic + English code | — | Bilingual for developer debugging |
| Admin platform | English | Arabic | Beza ops team; English-first |

## 25.2 RTL Implementation

| Aspect | Implementation |
|---|---|
| Layout direction | `dir="rtl"` on all education pages (HTML) |
| Flutter | `Directionality.rtl` wrapper |
| Text alignment | Right-aligned for Arabic content |
| Number formatting | Hindu-Arabic (123) preferred; Eastern Arabic (١٢٣) optional toggle |
| Date format | `dd/mm/yyyy` (Syrian convention) |
| Time format | 24-hour (hh:mm) |
| Currency symbol | "ل.س" after amount: "٨٨٠,٠٠٠ ل.س" |
| Icons/arrows | Mirrored for RTL (left-arrow → right-arrow for back) |

## 25.3 Translation Files

### Flutter ARB Example (`app_ar.arb`)

```json
{
  "appName": "بيزا للتعليم",
  "educationHomeTitle": "التعليم",
  "payNow": "ادفع الآن",
  "feeBreakdown": "تفاصيل الرسوم",
  "totalDue": "المبلغ المستحق",
  "dueDate": "تاريخ الاستحقاق",
  "overdue": "متأخر",
  "paid": "مدفوع",
  "partial": "مدفوع جزئياً",
  "receiptNumber": "رقم الإيصال",
  "downloadReceipt": "تحميل الإيصال",
  "scheduleAutoPay": "جدولة الدفع التلقائي",
  "applyInstalments": "طلب تقسيط",
  "paymentHistory": "سجل المدفوعات",
  "addChild": "إضافة طالب",
  "schoolName": "اسم المدرسة",
  "studentName": "اسم الطالب",
  "grade": "الصف",
  "section": "الشعبة",
  "amountSYP": "{amount} ل.س",
  "daysOverdue": "متأخر {days} يوم",
  "lateFee": "غرامة تأخير: {amount} ل.س",
  "sendReminder": "إرسال تذكير",
  "bulkReminder": "تذكير جماعي",
  "exportCSV": "تصدير إكسل",
  "paymentSuccessful": "تم الدفع بنجاح",
  "paymentFailed": "فشل الدفع",
  "insufficientBalance": "الرصيد غير كافٍ",
  "retry": "إعادة المحاولة",
  "contactSupport": "تواصل مع الدعم",
  "siblingDiscount": "خصم أخوي",
  "earlyBirdDiscount": "خصم التسجيل المبكر",
  "scholarship": "منحة دراسية"
}
```

## 25.4 Cultural Considerations

| Consideration | Implementation |
|---|---|
| **Gender** | Use neutral Arabic or male-default; school forms capture gender |
| **Honorifics** | "الأستاذ" for teachers, "الدكتور" for professors, "السيد/السيدة" for parents |
| **Friday closure** | No automated SMS on Friday (holy day) before 16:00 |
| **Ramadan hours** | Reminders sent after Iftar (sunset); business hours shift |
| **Holiday calendar** | Syrian holidays: Independence (17 Apr), Eid al-Fitr, Eid al-Adha, Revolution Day (8 Mar), Christmas (25 Dec) — no auto-payments or reminders on these days |
| **Holiday-aware scheduling** | Fee due dates should not fall on Eid holidays; system suggests alternative |
| **Religious text** | Avoid imagery; use geometric patterns in receipts, not figures |
| **Colour** | Green (positive/paid), Red (overdue), Amber (pending) — green culturally positive |

## 25.5 Arabic UI Considerations

| Element | Arabic UX |
|---|---|
| Numbers | Space as thousands separator: "٨٨٠ ٠٠٠" or "880,000" |
| Percentages | "٢٪" or "2%" |
| Phone numbers | +963 XX XXX XXXX format |
| Addresses | Governorate → City → District → Street (descending) |
| Names | Full name with lineage: "ليلى الخطيب" (given + family) |
| Keyboard | Arabic keyboard for text input; Latin for numbers/emails |
| Font | Arabic-optimised: Noto Naskh Arabic, Tajawal, or Cairo |
| Font weight | Regular for body, Bold for headings; avoid light/ultralight (illegible in Arabic) |
