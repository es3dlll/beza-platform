# 29 — FAQ

---

## General

### ما هي خدمة رواتب بيزة؟
Beza Payroll is a digital salary distribution platform. Companies upload salary data and Beza debits the company account and credits all employee wallets instantly.

### هل أحتاج إلى حساب بنكي لاستخدام الخدمة؟
Companies need a bank account to fund their Beza payroll account. Employees only need a smartphone and a Beza wallet (no bank account required).

### هل الخدمة متوافقة مع الشريعة الإسلامية؟
The T+0 settlement model (pre-funded) is Sharia-compliant as a service fee. T+1 model is under Sharia board review. Salary advances are interest-free.

## Companies

### كيف يمكن لشركتي التسجيل؟
Go to `dashboard.beza.sy/payroll/register`, fill the form, upload your commercial license and tax certificate. A Beza admin will review within 24 hours.

### ما هي رسوم الخدمة؟
0.5 % of total batch amount (capped at SYP 500,000 per batch). Monthly platform fee: SYP 50,000 (SME) or SYP 200,000 (Enterprise).

### كم من الوقت تستغرق معالجة الدفعة؟
Typically 1–5 seconds per 1,000 employees. A batch of 150 employees processes in under 2 seconds.

### ماذا يحدث إذا كان رصيد الشركة غير كافٍ؟
The system shows a clear warning with the exact shortfall amount before processing. The batch will not proceed until sufficient funds are deposited.

### هل يمكنني جدولة دفعة ليوم لاحق؟
Yes. You can schedule a batch for any future date. The system will process it automatically at 08:00 on the scheduled date.

### كيف يمكنني إعادة محاولة الدفعات الفاشلة؟
From the batch detail page, click "Retry" for individual employees or "Retry All". The system automatically retries failed transactions 3 times before manual intervention is needed.

## Employees

### كيف أستلم راتبي عبر بيزة؟
Your employer will register you. You'll receive an SMS with a link to activate your Beza wallet. Once activated, future salaries will be auto-credited.

### هل هناك رسوم على استلام الراتب؟
No. Receiving your salary is completely free. Withdrawals and transfers may have standard Beza wallet fees.

### هل يمكنني الحصول على كشف راتب ورقي؟
You can download a PDF payslip from the Beza app anytime. It includes the full salary breakdown and Beza's digital verification seal.

### ماذا أفعل إذا لم أستلم راتبي؟
1. Check your Beza wallet balance
2. Check notifications for any issues (e.g., wallet not activated)
3. Contact your company HR
4. If unresolved, contact Beza support: support@beza.sy

### هل يمكنني الحصول على سلفة من راتبي؟
Salary advances (up to SYP 500,000) will be available in Phase 2. You can request an advance from the Beza app.

## Technical

### ما هي صيغة ملف CSV المطلوب؟
CSV must have these columns: `employee_id`, `full_name_ar`, `amount`, `currency` (optional). Download the template from the dashboard.

### هل يمكنني ربط نظام ERP الخاص بي؟
Yes. Beza provides a REST API for direct integration. Contact support to request API keys.

### هل البيانات آمنة؟
All data is encrypted at rest (AES-256) and in transit (TLS 1.3). Servers are located in Damascus, Syria. Beza is licensed by the Central Bank of Syria.

### What happens if a payment fails?
The system automatically retries 3 times (5 min, 30 min, 2 hours). If all fail, the company HR is notified and can retry manually from the dashboard.

### How do I contact support?
- Email: support@beza.sy
- Phone: +963-11-XXX-XXXX (Sat–Thu, 08:00–17:00)
- In-app: Help center → Chat
