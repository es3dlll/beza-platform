# تقرير تسليم المهمة A1: تسجيل مستخدم + إنشاء محفظة تلقائي

**المرحلة المنفذة:** التطوير — TDD من البداية (7 اختبارات → 7 نجاح)

**الروابط المرجعية:**
- التوثيق: `tasks/01-auth/A1-register.md`
- الكود: `backend/app/Models/User.php`, `backend/app/Models/Wallet.php`, `backend/app/Modules/{Auth,Wallet}/`
- الاختبارات: `backend/tests/Feature/Auth/RegisterTest.php` (7 اختبارات)
- الفرع: `feature/A1-register` (`98c3db0`)

**المقاييس:**

| المقياس | القيمة |
|---------|--------|
| اختبارات | 7 ✅ (40 assertion) |
| هجرات | 2 (`wallets`, `users+phone+kyc`) |
| ملفات جديدة | 11 |
| رسائل خطأ | بالعربية |
| المحافظ | SYP + USD تلقائياً برصيد 0.0000 |

**التدفق المختبر:**
```
POST /api/register {name, email, phone, password}
  → 201 {user, token, wallets[SYP, USD]}
  → GET /api/user (Bearer token) → 200 {email}
```

**بوّابات الجودة:**
- [x] TDD — اختبارات قبل الكود
- [x] `php artisan test` — 9/9 ✅ (2 قديم + 7 جديد)
- [x] رسائل خطأ بالعربية في `RegisterRequest`
- [x] المحفظة: `unique(user_id, currency)` — لا يمكن تكرار العملة لنفس المستخدم
- [x] كلمة المرور: ≥8 أحرف + حرف كبير + رقم + تأكيد
- [x] الهاتف: صيغة سورية (09xxxxxxxx)
- [x] التوثيق محدّث (معايير القبول)

**المؤجّلات:**
1. **Rate limiting** (3/ساعة) — يحتاج `ThrottleRequests` middleware مخصص
2. **OpenAPI spec** — يتم إضافتها مع توثيق API كامل لاحقاً
3. **التحقق من الهاتف (OTP)** — المرحلة الثانية

**الخطوة التالية المقترحة:**
- A2: تسجيل الدخول (Login) — `/api/login`
- أو A3: تسجيل الخروج (Logout) — `/api/logout`
- أو الانتقال لوحدة المحفظة (Wallet)
