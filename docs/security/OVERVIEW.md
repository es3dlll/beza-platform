# Security Model - نموذج الأمان

## Zero Trust Security

لا نثق بأي طلب - التحقق في كل طبقة.

```
Client → TLS 1.3 → WAF → Rate Limit → Auth → Policy → API → Audit Log
```

## الأعمدة الأساسية

| العمود | الوصف | الحالة |
|--------|-------|--------|
| **Data Encryption** | AES-256 للبيانات الحساسة + TLS 1.3 | مطلوب |
| **Access Control** | RBAC + ABAC + JWT (15 دقيقة) | مطلوب |
| **Audit Logging** | سجل تدقيق WORM لكل عملية مالية | مطلوب |
| **Rate Limiting** | 30 req/min للـ API، 3 محاولات/ساعة للدخول | مطلوب |
| **Input Validation** | منع SQL injection/XSS في كل المدخلات | مطلوب |
| **Device Binding** | ربط الجهاز للمعاملات المالية | مطلوب |

## JWT Structure

```
Header:  { "alg": "HS256", "typ": "JWT" }
Payload: {
  "sub": "user_ulid",
  "session": "session_id",
  "device": "device_fingerprint",
  "iat": 1234567890,
  "exp": 1234568790  // 15 دقيقة
}
Refresh Token: 7 أيام (دوران تلقائي)
```

## سجل التدقيق (Audit Log)

كل عملية مالية تسجل في Audit Log:
- معرف المستخدم (ULID)
- الطابع الزمني (UTC)
- عنوان IP + device fingerprint
- نوع العملية
- المعاملات المالية الكاملة (قبل/بعد)
- النتيجة (نجاح/فشل + سبب الفشل)

السجل غير قابل للتعديل أو الحذف (WORM).

## الرجوع للمزيد

- [معايير التشفير](STANDARDS.md) - Encryption, Auth, Authorization details
- [سياسة AML/CFT](../compliance/AML.md) - Anti-Money Laundering
- [متطلبات KYC](../compliance/KYC.md) - Know Your Customer
- [التكامل مع المصرف المركزي](../compliance/CBS.md)
