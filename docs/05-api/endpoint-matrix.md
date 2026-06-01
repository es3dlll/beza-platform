# Endpoint Matrix — مصفوفة نقاط API

> **الهدف:** ربط كل وحدة بيزا بمسارات API المطلوبة  
> **الأساس:** جميع المسارات تبدأ بـ `/v1/{module}`  
> **الحالة:** توثيق معماري — لا يوجد كود بعد

---

## Core Modules

| الوحدة | الطريقة | المسار | الوصف | المصادقة | الحالة |
|--------|---------|-------|-------|----------|--------|
| **Identity** | POST | `/v1/auth/register` | تسجيل مستخدم جديد | لا | موثق |
| | POST | `/v1/auth/verify-otp` | التحقق من OTP | لا | موثق |
| | POST | `/v1/auth/login` | تسجيل الدخول | لا | موثق |
| | POST | `/v1/auth/refresh` | تجديد التوكن | Refresh Token | موثق |
| | POST | `/v1/auth/logout` | تسجيل الخروج | JWT | موثق |
| | GET | `/v1/users/{id}` | بيانات المستخدم | JWT | موثق |
| | PUT | `/v1/users/{id}` | تحديث الملف الشخصي | JWT | موثق |
| **Wallet** | POST | `/v1/wallets` | إنشاء محفظة | JWT + Device | موثق |
| | GET | `/v1/wallets/{id}` | عرض المحفظة والرصيد | JWT | موثق |
| | GET | `/v1/wallets/{id}/transactions` | حركات المحفظة | JWT | موثق |
| **Ledger** | GET | `/v1/ledger/accounts` | شجرة الحسابات | JWT + Admin | موثق |
| | GET | `/v1/ledger/journal` | دفتر اليومية | JWT + Admin | موثق |
| | GET | `/v1/ledger/trial-balance` | ميزان المراجعة | JWT + Finance | موثق |
| **CoreFinancialEngine** | POST | `/v1/cfe/hold` | تجميد مبلغ | JWT + Device | موثق |
| | POST | `/v1/cfe/release` | تحرير مبلغ | JWT + Device | موثق |
| | POST | `/v1/cfe/transfer` | تحويل مالي | JWT + Device | موثق |
| | POST | `/v1/cfe/reverse` | عكس معاملة | JWT + Admin | موثق |
| **Compliance** | POST | `/v1/compliance/kyc/upgrade` | طلب ترقية KYC | JWT | موثق |
| | GET | `/v1/compliance/kyc/status` | حالة التحقق | JWT | موثق |
| | POST | `/v1/compliance/report` | إبلاغ عن نشاط مشبوه | JWT + Compliance | موثق |

## Financial Services

| الوحدة | الطريقة | المسار | الوصف | المصادقة | الحالة |
|--------|---------|-------|-------|----------|--------|
| **FX** | GET | `/v1/fx/rates` | أسعار الصرف | JWT | موثق |
| | POST | `/v1/fx/convert` | تنفيذ صرف | JWT + Device | موثق |
| **Remittance** | POST | `/v1/remittances` | إنشاء حوالة | JWT + Device | موثق |
| | GET | `/v1/remittances/{id}` | تفاصيل الحوالة | JWT | موثق |
| **Agent** | GET | `/v1/agents/nearby` | أقرب الوكلاء | JWT | موثق |
| | POST | `/v1/agents/cash-in` | إيداع نقدي | JWT + Device | موثق |
| | POST | `/v1/agents/cash-out` | سحب نقدي | JWT + Device | موثق |
| **Settlement** | POST | `/v1/settlements` | تنفيذ تسوية | JWT + Finance | موثق |
| | GET | `/v1/settlements/reports` | تقارير التسوية | JWT + Finance | موثق |
| **Bills** | GET | `/v1/bills/providers` | مزودو الفواتير | JWT | موثق |
| | POST | `/v1/bills/pay` | دفع فاتورة | JWT + Device | موثق |

## Merchant Services

| الوحدة | الطريقة | المسار | الوصف | المصادقة | الحالة |
|--------|---------|-------|-------|----------|--------|
| **Merchant** | POST | `/v1/merchants` | تسجيل تاجر | JWT | موثق |
| | POST | `/v1/merchants/qr/generate` | إنشاء QR | JWT | موثق |
| | POST | `/v1/merchants/qr/pay` | دفع QR | JWT + Device | موثق |
| **Payroll** | POST | `/v1/payroll/batches` | إنشاء دفعة رواتب | JWT + Finance | موثق |
| | GET | `/v1/payroll/batches/{id}` | حالة الدفعة | JWT | موثق |
| **Savings** | POST | `/v1/savings/goals` | إنشاء هدف ادخاري | JWT | موثق |
| | GET | `/v1/savings/goals/{id}` | تفاصيل الهدف | JWT | موثق |

## Payment Instruments

| الوحدة | الطريقة | المسار | الوصف | المصادقة | الحالة |
|--------|---------|-------|-------|----------|--------|
| **Cards** | POST | `/v1/cards` | إصدار بطاقة | JWT + Device | موثق |
| | GET | `/v1/cards/{id}` | تفاصيل البطاقة | JWT | موثق |
| **Financing** | POST | `/v1/financing/applications` | تقديم طلب تمويل | JWT | موثق |
| | GET | `/v1/financing/applications/{id}` | حالة الطلب | JWT | موثق |

## Cross-Cutting

| الوحدة | الطريقة | المسار | الوصف | المصادقة | الحالة |
|--------|---------|-------|-------|----------|--------|
| **Fraud** | GET | `/v1/fraud/cases` | قضايا الاحتيال | JWT + Compliance | موثق |
| | POST | `/v1/fraud/rules` | إدارة قواعد الكشف | JWT + Admin | موثق |
| **Notification** | POST | `/v1/notifications/send` | إرسال إشعار | JWT | موثق |
| | GET | `/v1/notifications/history` | سجل الإشعارات | JWT | موثق |
| **Analytics** | GET | `/v1/analytics/dashboard` | لوحة الإحصائيات | JWT + Admin | موثق |
| | GET | `/v1/analytics/reports/{type}` | تقارير مخصصة | JWT + Admin | موثق |
| **Admin** | GET | `/v1/admin/users` | إدارة المستخدمين | JWT + Admin | موثق |
| | POST | `/v1/admin/settings` | إعدادات النظام | JWT + Admin | موثق |

---

## معايير التسمية

- المسارات: `/{version}/{module}/{resource}`
- الموارد: جمع (users, wallets, transactions)
- المعرفات: ULID في المسار
- الإصدار: `v1` كبادئة (قابلة للترقية لاحقاً)

## المصادقة والصلاحيات

- `لا`: نقطة عامة (تسجيل، دخول)
- `JWT`: يتطلب توكن وصول صالح (15 دقيقة)
- `JWT + Device`: يتطلب ربط الجهاز للمعاملات المالية
- `JWT + {Role}`: يتطلب دوراً محدداً (Admin, Finance, Compliance)

## ملاحظات

- هذه المصفوفة توثق واجهة API المتوقعة — لا يوجد كود منفذ بعد
- عند كتابة الكود، أضف النقطة في ملف `Routes/api.php` للوحدة
- حدّث مواصفات OpenAPI في `docs/api/openapi-v1.yaml`
