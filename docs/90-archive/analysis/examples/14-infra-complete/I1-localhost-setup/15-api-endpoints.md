# 15 - قائمة نقاط API الكاملة (API Endpoints)

## المصادقة (Auth)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| POST | /api/v1/auth/register | تسجيل مستخدم جديد |
| POST | /api/v1/auth/login | تسجيل الدخول |
| POST | /api/v1/auth/logout | تسجيل الخروج |
| POST | /api/v1/auth/otp | طلب OTP |
| POST | /api/v1/auth/verify-otp | التحقق من OTP |

## المحفظة (Wallet)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| GET | /api/v1/wallet | عرض الرصيد |
| POST | /api/v1/wallet/exchange | تحويل بين العملات |

## التحويلات (Transfers)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| POST | /api/v1/transfer | تحويل P2P |
| GET | /api/v1/transactions | قائمة المعاملات |
| GET | /api/v1/transactions/{id} | تفاصيل معاملة |

## التجار (Merchants)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| POST | /api/v1/merchant/register | تسجيل تاجر |
| POST | /api/v1/merchant/products | إنشاء منتج |
| POST | /api/v1/merchant/pay | معالجة دفع |

## الوكلاء (Agents)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| POST | /api/v1/agent/cash-in | إيداع نقدي |
| POST | /api/v1/agent/cash-out | سحب نقدي |

## الصفقات (Deals)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| GET | /api/v1/deals | قائمة الصفقات |
| POST | /api/v1/deals/{id}/invest | استثمار |

## الإشعارات (Notifications)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| GET | /api/v1/notifications | قائمة الإشعارات |
| POST | /api/v1/notifications/{id}/read | تحديد كمقروء |

## الأدمن (Admin)

| الطريقة | المسار | الوصف |
|---------|--------|-------|
| GET | /api/v1/admin/users | إدارة المستخدمين |
| GET | /api/v1/admin/audit-logs | سجل التدقيق |
| GET | /api/v1/admin/fraud/report | تقرير الاحتيال |
