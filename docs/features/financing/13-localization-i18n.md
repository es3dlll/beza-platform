# التعريب والتدويل — Localization & i18n

## Language Strategy
| Level | Language | Direction | Notes |
|-------|----------|-----------|-------|
| Primary | Arabic (ar) | RTL | Modern Standard Arabic (MSA) with Levantine colloquial in notifications |
| Secondary | English (en) | LTR | For diaspora users, developer docs |
| Future | Kurdish (ku), Turkish (tr) | RTL/LTR | For regional expansion |

## Key Translation Strings (Arabic)

### Product Names
| Key | Arabic | English |
|-----|--------|---------|
| product.qard_hasan | قرض حسن | Qard Hasan (Benevolent Loan) |
| product.murabaha | مرابحة | Murabaha (Cost-Plus Financing) |
| product.micro | تمويل المنشآت الصغيرة | Micro-Enterprise Financing |

### Application Flow
| Key | Arabic |
|-----|--------|
| apply.title | تقديم طلب تمويل |
| apply.amount | المبلغ المطلوب |
| apply.term | المدة (بالأيام) |
| apply.purpose | الغرض من التمويل |
| apply.documents | المستندات المرفقة |
| apply.guarantor | معلومات الضامن |
| apply.submit | تقديم الطلب |
| apply.success | تم إرسال الطلب بنجاح |
| apply.review | مراجعة الطلب |

### Status Labels
| Key | Arabic |
|-----|--------|
| status.draft | مسودة |
| status.submitted | قيد المراجعة |
| status.underwriting | تحت التقييم |
| status.approved | تمت الموافقة |
| status.rejected | مرفوض |
| status.disbursed | تم الصرف |
| status.active | نشط |
| status.completed | مكتمل |
| status.defaulted | متعثر |
| status.restructured | معاد جدولته |

### Financial Terms
| Key | Arabic |
|-----|--------|
| finance.principal | رأس المال |
| finance.profit | الربح |
| finance.admin_fee | الرسوم الإدارية |
| finance.total | الإجمالي |
| finance.installment | القسط |
| finance.due_date | تاريخ الاستحقاق |
| finance.late_fee | رسوم التأخير |
| finance.charity | الصدقات |
| finance.schedule | جدول السداد |
| finance.balance | الرصيد المتبقي |

### Sharia Terms
| Key | Arabic |
|-----|--------|
| sharia.compliant | متوافق مع أحكام الشريعة الإسلامية |
| sharia.no_riba | بدون ربا |
| sharia.contract | عقد شرعي |
| sharia.certification | شهادة الامتثال الشرعي |
| sharia.charity_note | رسوم التأخير تذهب إلى الصدقات |
| sharia.ownership | تنتقل ملكية السلعة بعد السداد الكامل |

### Error Messages
| Key | Arabic |
|-----|--------|
| error.insufficient_balance | الرصيد غير كافٍ لإتمام العملية |
| error.kyc_required | يرجى إكمال التحقق من الهوية أولاً |
| error.max_active_loans | لديك الحد الأقصى من التمويلات النشطة |
| error.offer_expired | انتهت صلاحية العرض |
| error.guarantor_not_found | لم يتم العثور على الضامن في تطبيق بيزا |
| error.amount_exceeds_max | المبلغ يتجاوز الحد المسموح |
| error.min_term_not_met | المدة أقل من الحد الأدنى |

## Number Formatting
```typescript
const formatCurrency = (amount: number, locale: 'ar' | 'en'): string => {
  if (locale === 'ar') {
    return `${amount.toLocaleString('ar-SA')} ل.س`;
  }
  return `SYP ${amount.toLocaleString('en-US')}`;
};

// Examples:
// Arabic: ١,٢٥٠,٠٠٠ ل.س
// English: SYP 1,250,000
```

## Date/Time Formatting
```typescript
// Arabic: ١٠ يونيو ٢٠٢٦
// English: June 10, 2026

// Arabic (short): ١٠/٦/٢٠٢٦
// English (short): 06/10/2026
```
