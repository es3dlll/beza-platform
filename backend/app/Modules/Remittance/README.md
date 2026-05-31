# وحدة التحويلات الدولية (Remittance Core)

## الهدف
إدارة دورة حياة التحويلات الدولية بالكامل: حساب الرسوم، قفل أسعار الصرف، فحص القوائم المحظورة، والامتثال العابر للحدود.

## الاعتمادات
- **FX** — أسعار الصرف عبر حدث RequestFXQuote
- **FinancialCore** — الخصم والإيداع عبر حدث InitiateLedgerTransfer
- **EventBus** — ناقل الأحداث الأساسي
- **Identity** — مستويات KYC للمستخدمين

## التدفق الرئيسي
1. استعلام سعر (Quote) → حساب الرسوم والهامش
2. بدء تحويل (Initiate) → قفل سعر + فحص امتثال
3. معالجة (Processing) → خصم/إيداع عبر CFE
4. تسوية (Settled) → تأكيد وإشعار
5. رفض/إلغاء/انتهاء → تحرير الأرصدة

## الحالات
PENDING → FX_LOCKED → COMPLIANCE_CHECK → PROCESSING → SETTLED
                                                      → REJECTED
                                                      → CANCELLED
                                                      → EXPIRED

## قنوات التواصل
الأحداث: RequestFXQuote, FXRateLocked, ComplianceReviewRequired, InitiateLedgerTransfer, RemittanceCompleted, ReleaseFXLock, CancelExpiredTransfer
