# SEC-001: تعقيم مدخلات JSON ومنع SQL Injection

**المعرف:** `SEC-001-JS`  
**الأولوية:** 🟠 P1  
**الوكيل المنفذ:** ⚙️ Backend  
**الحالة:** تصميم

---

## 1. الوصف

قاعدة (Validation Rule) تقوم بفحص جميع مدخلات JSON قبل أي استعلام قاعدة بيانات لمنع:
- SQL Injection
- XSS (Cross-Site Scripting)
- Command Injection
- NoSQL Injection

## 2. الأنماط الممنوعة

| الفئة | الأنماط |
|-------|---------|
| **SQL DML** | `UNION SELECT`, `INSERT INTO`, `UPDATE SET`, `DELETE FROM` |
| **SQL DDL** | `DROP TABLE`, `ALTER TABLE`, `TRUNCATE` |
| **SQL Execution** | `EXEC`, `EXECUTE`, `xp_cmdshell` |
| **SQL File** | `INTO OUTFILE`, `INTO DUMPFILE`, `LOAD_FILE` |
| **Blind SQLi** | `SLEEP()`, `BENCHMARK()`, `pg_sleep`, `WAITFOR DELAY` |
| **XSS** | `<script>`, `javascript:`, `on{event}=` |
| **PHP Injection** | `$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER` |
| **Encoding** | `base64()`, `hex()`, `unhex()`, `char()`, `concat()` |

## 3. آلية العمل

```
JSON input → SanitizedJsonInput Rule
  ├─ تحويل إلى string (JSON encode إذا array)
  ├─ فحص ضد BLOCKED_PATTERNS
  │   ├─ تطابق → → فشل مع رسالة: "يحتوي على أنماط غير مسموح بها"
  │   └─ لا تطابق → → نجاح
  └─ (اختياري) تنقية HTML بـ strip_tags()
```

## 4. التطبيق

### 4.1 في Form Requests

```php
'search_query' => ['required', 'string', new SanitizedJsonInput],
'metadata'     => ['sometimes', 'array', new SanitizedJsonInput],
```

### 4.2  في المتحكمات

```php
$sanitized = SanitizedJsonInput::sanitize($request->all());
```

## 5. هيكل الخطأ

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "بيانات الإدخال غير صالحة. يرجى التحقق من الحقول.",
    "fields": {
      "search_query": ["search_query يحتوي على أنماط غير مسموح بها."]
    }
  }
}
```

## 6. خطة الاختبارات

| # | السيناريو | المتوقع |
|---|-----------|---------|
| 1 | JSON نظيف `{"name": "أحمد"}` | نجاح |
| 2 | SQL Injection: `1'; DROP TABLE users; --` | فشل |
| 3 | UNION SELECT | فشل |
| 4 | XSS: `<script>alert(1)</script>` | فشل |
| 5 | Blind SQLi: `SLEEP(5)` | فشل |
| 6 | Nested JSON نظيف | نجاح |
| 7 | HTML في حقل: `<b>text</b>` | strip_tags → `text` |
| 8 | JavaScript protocol: `javascript:alert(1)` | فشل |

## 7. معايير القبول

- [ ] فحص جميع مدخلات JSON قبل أي استعلام
- [ ] 20+ نمط ممنوع (SQL, XSS, Command Injection)
- [ ] دالة `sanitize()` للتنقية الاختيارية
- [ ] رسالة خطأ بالعربية
- [ ] القاعدة قابلة لإعادة الاستخدام عبر Form Requests
