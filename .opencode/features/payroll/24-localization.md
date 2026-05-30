# 24 — Localization

---

## Language Strategy

- **Primary:** Arabic (ar-SY) — Syrian Arabic dialect in UI, Modern Standard Arabic (MSA) in formal communications
- **Secondary:** English (en) — for API responses, developer docs, system logs
- **Fallback:** Arabic — if translation key missing, show Arabic

## Locale Configuration

| Property | Arabic (ar-SY) |
|----------|----------------|
| Direction | RTL |
| Date format | ٢٩ مايو ٢٠٢٦ |
| Time format | ١٠:٠٥ ص |
| Number format | ١٬٢٠٠٬٠٠٠ (Arabic-Indic digits optional) |
| Currency format | ١٬٢٠٠٬٠٠٠ ل.س |
| First day of week | Sunday |
| Weekend | Friday – Saturday |

## Translation Keys (Sample)

```json
{
  "payroll.batch.new": "دفعة جديدة",
  "payroll.batch.list": "قائمة الدفعات",
  "payroll.batch.detail": "تفاصيل الدفعة",
  "payroll.batch.confirm": "تأكيد الدفعة",
  "payroll.batch.processing": "جارٍ معالجة الدفعة...",
  "payroll.batch.completed": "تمت معالجة الدفعة بنجاح",
  "payroll.batch.failed": "فشلت معالجة الدفعة",
  "payroll.batch.partial": "تمت المعالجة مع وجود أخطاء",
  "payroll.employee.add": "إضافة موظف",
  "payroll.employee.edit": "تعديل بيانات الموظف",
  "payroll.employee.terminate": "إنهاء خدمة الموظف",
  "payroll.employee.import": "استيراد من ملف",
  "payroll.balance": "الرصيد المتاح",
  "payroll.amount": "المبلغ",
  "payroll.fee": "الرسوم",
  "payroll.total": "المجموع",
  "payroll.status": "الحالة",
  "payroll.date": "التاريخ",
  "payroll.payslip": "كشف الراتب",
  "payroll.download_payslip": "تنزيل كشف الراتب",
  "payroll.download_all": "تنزيل الكشوفات جميعها",
  "payroll.retry": "إعادة محاولة",
  "payroll.retry_all": "إعادة محاولة الكل",
  "payroll.settle": "تسوية",
  "payroll.settlement_period": "دورة التسوية",
  "payroll.insufficient_balance": "رصيد غير كافٍ",
  "payroll.wallet_not_active": "المحفظة غير مفعلة",
  "payroll.user_not_found": "المستخدم غير موجود",
  "payroll.cfe_error": "خطأ في النظام المالي",
}
```

## Arabic-specific UI Considerations

| Concern | Solution |
|---------|----------|
| RTL layout | `dir="rtl"` on root; mirrored margins/padding |
| Font rendering | Noto Naskh Arabic (web) + Noto Sans Arabic (mobile) |
| Number input | Accept Arabic-Indic digits (١٢٣) + Western digits (123) |
| Search / filter | Arabic-friendly fuzzy search (trigram matching) |
| CSV template | Column headers in Arabic: `رقم_الموظف`, `الاسم_الكامل`, `المبلغ`, `العملة` |
| PDF generation | Arabic text in PDF requires embedded Arabic font |

## Translation Management

- i18n files: `locales/ar.json`, `locales/en.json`
- Context: `payroll.batch.confirm` used in PIN confirmation modal
- Plurals: handled via ICU MessageFormat for Arabic plural rules
