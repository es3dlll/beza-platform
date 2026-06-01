# SEC-001: معالج الأخطاء الآمن (Secure Error Handler)

**المعرف:** `SEC-001-EH`  
**الأولوية:** 🟠 P1  
**الوكيل المنفذ:** ⚙️ Backend  
**الحالة:** تصميم

---

## 1. الوصف

معالج أخطاء موحد يضمن عدم تسريب معلومات تقنية حساسة في بيئة الإنتاج:
- Stack traces
- DB schema (أسماء الجداول، الأعمدة)
- Internal paths (مسارات الملفات)
- Credentials

## 2. تصنيف الأخطاء والاستجابات

| نوع الخطأ | كود الحالة | كود الخطأ | رسالة المستخدم | تسجيل داخلي |
|-----------|-----------|-----------|----------------|-------------|
| Validation | 422 | `VALIDATION_ERROR` | بيانات الإدخال غير صالحة | ❌ |
| Authentication | 401 | `UNAUTHENTICATED` | يرجى تسجيل الدخول أولاً | ❌ |
| Authorization | 403 | `FORBIDDEN` | ليس لديك صلاحية | ❌ |
| Not Found | 404 | `NOT_FOUND` | المورد المطلوب غير موجود | ❌ |
| Rate Limit | 429 | `RATE_LIMIT_EXCEEDED` | طلبات كثيرة جداً | ❌ |
| Database | 500 | `DATABASE_ERROR` | حدث خطأ في قاعدة البيانات | ✅ |
| Service | 503 | `SERVICE_UNAVAILABLE` | الخدمة غير متاحة حالياً | ✅ |
| Generic | 500 | `INTERNAL_ERROR` | حدث خطأ داخلي في الخادم | ✅ |

## 3. آلية العمل

```
Throwable → Handler (register)
  ├─ JSON request? → SecureErrorHandler::handle()
  │   ├─ ValidationException → 422 + تفاصيل الحقول
  │   ├─ AuthenticationException → 401 + رسالة عامة
  │   ├─ AuthorizationException → 403 + رسالة عامة
  │   ├─ NotFoundHttpException → 404 + رسالة عامة
  │   ├─ HttpException → حسب الكود (429/503/...)
  │   ├─ QueryException → 500 + DATABASE_ERROR (تسجيل كامل)
  │   └─ Throwable → 500 + INTERNAL_ERROR + trace_id
  └─ Non-JSON → render طبيعي
```

## 4. هيكل الاستجابة

### إنتاج (debug=false):
```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "حدث خطأ داخلي في الخادم. يرجى المحاولة لاحقاً.",
    "trace_id": "a1b2c3d4e5f67890"
  }
}
```

### تطوير (debug=true):
```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "حدث خطأ داخلي في الخادم. يرجى المحاولة لاحقاً.",
    "trace_id": "a1b2c3d4e5f67890",
    "debug": "خطأ في الاتصال بقاعدة البيانات (تم إخفاء الحساسيات)"
  }
}
```

## 5. تعقيم معلومات التصحيح

المفاتيح الحساسة التي يتم إخفاؤها تلقائياً:

| المفتاح | البديل |
|---------|--------|
| `password=...` | `password=***` |
| `secret=...` | `secret=***` |
| `token=...` | `token=***` |
| `api_key=...` | `api_key=***` |
| `credit_card=...` | `credit_card=***` |
| `cvv=...` | `cvv=***` |
| `pin=...` | `pin=***` |
| `ssn=...` | `ssn=***` |
| `national_id=...` | `national_id=***` |

## 6. Trace ID

- طول: 32 حرف hex (16 بايت random)
- يستخدم لربط الخطأ في logs مع استجابة المستخدم
- يظهر في الاستجابة والإدخال في السجل

## 7. خطة الاختبارات

| # | السيناريو | المتوقع |
|---|-----------|---------|
| 1 | Validation error في API | `422 VALIDATION_ERROR` |
| 2 | Unauthenticated request | `401 UNAUTHENTICATED` |
| 3 | Forbidden request | `403 FORBIDDEN` |
| 4 | Route غير موجودة | `404 NOT_FOUND` |
| 5 | Rate limit (429) | رسالة "طلبات كثيرة" |
| 6 | DB connection error | `500 DATABASE_ERROR` — بدون schema |
| 7 | Generic RuntimeException | `500 INTERNAL_ERROR` + `trace_id` |
| 8 | رسالة خطأ تحتوي credentials | تعقيم → `password=***` |
| 9 | Non-JSON request | render طبيعي بدون JSON |

## 8. معايير القبول

- [ ] لا stack traces في الإنتاج
- [ ] لا تسريب لـ DB schema
- [ ] لا مسارات داخلية في الرسائل
- [ ] رسائل خطأ بالعربية
- [ ] `trace_id` لربط الأخطاء في logs
- [ ] `debug=true` فقط في البيئة المحلية
- [ ] تعقيم المفاتيح الحساسة تلقائياً
