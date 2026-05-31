# وحدة التجار ونقاط البيع (Merchant & POS Core)

## الهدف
إدارة هوية التجار، توليد وفحص رموز QR الديناميكية، دورة حياة الفواتير، معالجة المدفوعات، التسوية اليومية، والامتثال الضريبي المحلي.

## الاعتمادات
- **FinancialCore** — معالجة الدفع عبر حدث ProcessInvoicePayment
- **Ledger** — تسوية أرصدة التجار عبر LedgerSettlementTransfer
- **EventBus** — ناقل الأحداث الأساسي

## التدفق الرئيسي
1. تسجيل تاجر → Onboard → تعيين مستوى امتثال
2. إنشاء فاتورة → QRToken (صلاحية 10 دقائق)
3. دفع فاتورة ← تحقق QR ← FinancialCore ← InvoicePaid
4. تسوية يومية ← جمع المدفوعات ← خصم عمولات ← LedgerSettlementTransfer

## حالات الفاتورة
DRAFT → PENDING_PAYMENT → PAID → REFUNDED
                                  → EXPIRED
                                  → CANCELLED

## قنوات التواصل
الأحداث: InvoiceCreated, InvoicePaymentInitiated, InvoicePaid, TriggerMerchantSettlement, RefundRequested, QRValidationFailed, TaxRecordCreated
