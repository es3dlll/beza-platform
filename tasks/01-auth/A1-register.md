# A1: تسجيل مستخدم جديد + إنشاء محفظة تلقائي

**المعرف:** `A1-register`  
**الوحدة:** 🔐 المصادقة  
**الأولوية:** 🔴 P0 — حرجة  
**التبعية:** لا شيء (أول مهمة)

---

## الهدف

تسجيل مستخدم جديد في المنصة مع إنشاء محفظة مزدوجة (SYP + USD) تلقائياً.

## التدفق

```
[POST] /api/register
  ├─ المدخلات: name, email, phone, password, password_confirmation
  ├─ التحقق:
  │   ├─ email فريد + صيغة صحيحة
  │   ├─ phone فريد + رقم سوري (09xx)
  │   └─ password >= 8 أحرف + حرف كبير + رقم
  ├─ إنشاء:
  │   ├─ User → users table
  │   ├─ Wallet SYP → wallets table
  │   └─ Wallet USD → wallets table
  └─ المخرجات:
      ├─ user (json)
      ├─ token (Sanctum)
      └─ wallets (array)
```

## قواعد العمل

- إنشاء محفظة SYP تلقائياً برصيد 0.0000
- إنشاء محفظة USD تلقائياً برصيد 0.0000
- إرسال OTP للتحقق من الهاتف (اختياري في المرحلة الأولى)
- رسالة ترحيبية عبر الإشعارات

## هيكل API

```
POST /api/register
Content-Type: application/json

{
  "name": "أحمد محمد",
  "email": "ahmed@example.com",
  "phone": "0933123456",
  "password": "SecurePass1",
  "password_confirmation": "SecurePass1"
}

Response 201:
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "أحمد محمد", ... },
    "token": "1|abc123...",
    "wallets": [
      { "id": 1, "currency": "SYP", "balance": "0.0000" },
      { "id": 2, "currency": "USD", "balance": "0.0000" }
    ]
  }
}
```

## هيكل قاعدة البيانات

```sql
-- users
id, name, email, phone, password, email_verified_at, phone_verified_at, kyc_level, status, created_at

-- wallets
id, user_id, currency, balance DECIMAL(18,4), blocked DECIMAL(18,4), status, created_at
```

## الاختبارات

| # | السيناريو | المتوقع |
|---|-----------|---------|
| 1 | تسجيل ببيانات صحيحة | `201` + مستخدم + محفظتين |
| 2 | إيميل موجود مسبقاً | `422` validation error |
| 3 | هاتف موجود مسبقاً | `422` validation error |
| 4 | كلمة مرور ضعيفة (أقل من 8) | `422` |
| 5 | رقم هاتف بصيغة خاطئة | `422` |
| 6 | Rate limit تجاوز (3 محاولات/ساعة) | `429` |

## معايير القبول

- [x] مستخدم جديد يُنشأ مع محفظتين تلقائياً
- [x] validation كامل لجميع الحقول
- [ ] Rate limiting: 3 محاولات/ساعة (مؤقت — يحتاج middleware مخصص)
- [x] رسالة خطأ بالعربية
- [ ] التوثيق: OpenAPI spec (مؤقت)
