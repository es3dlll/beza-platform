# Security — الأمان

> **الهدف:** توثيق نموذج الأمان الشامل لمنصة بيزا (Zero Trust Architecture)  
> **الجمهور المستهدف:** مهندسو الأمان، مطورو جميع الأقسام، مسؤولي الامتثال  
> **العلاقة:** هذا القسم يحدد المعايير الأمنية المطبقة في كل طبقة من النظام

---

## الملفات

| الملف | الوصف | المستوى |
|-------|-------|---------|
| [`OVERVIEW.md`](OVERVIEW.md) | نموذج الأمان: Zero Trust، JWT، Audit Log، Device Binding | أساسي |
| [`STANDARDS.md`](STANDARDS.md) | معايير تفصيلية: المصادقة، التفويض، التشفير، القواعد الصارمة | تفصيلي |

---

## نموذج الأمان (Zero Trust)

```
Client → TLS 1.3 → WAF → Rate Limit → Auth → Policy → API → Audit Log
```

**المبدأ:** لا نثق بأي طلب. التحقق في كل طبقة من أول طلب إلى آخر استجابة.

---

## الأعمدة الأساسية

| العمود | الوصف | المرجع |
|--------|-------|--------|
| **المصادقة** | JWT (15 دقيقة) + Refresh Token (7 أيام) + Device Binding | [`STANDARDS.md`](STANDARDS.md) |
| **التفويض** | RBAC + ABAC لكل نقطة API | [`shared/security/02-authorization.md`](../shared/security/02-authorization.md) |
| **التشفير** | AES-256-GBM + TLS 1.3 + Bcrypt | [`shared/security/03-encryption.md`](../shared/security/03-encryption.md) |
| **التدقيق** | Audit Log WORM لكل عملية مالية | [`OVERVIEW.md`](OVERVIEW.md) |
| **تقييد المعدل** | 30 req/min للـ API، 3 محاولات/ساعة للدخول | [`DEPLOYMENT.md`](../infrastructure/DEPLOYMENT.md) |

---

## العلاقة مع الأقسام الأخرى

- **العمارة** (`../architecture/PRINCIPLES.md`): مبادئ الأمان في المعمارية
- **المعايير المشتركة/الأمان** (`../shared/security/`): تفاصيل المصادقة، التفويض، التشفير
- **الامتثال** (`../compliance/`): متطلبات AML/KYC الأمنية
- **البنية التحتية** (`../infrastructure/DEPLOYMENT.md`): أمان الخادم والشبكة
- **المعايير المشتركة/المراقبة** (`../shared/observability/`): كشف الاختراق والتنبيهات الأمنية
