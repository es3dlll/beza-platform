# SEC-001: نظام تحديد معدل الطلبات (Rate Limiting)

**المعرف:** `SEC-001-RL`  
**الأولوية:** 🔴 P0  
**الوكيل المنفذ:** ⚙️ Backend  
**الحالة:** تصميم

---

## 1. الوصف

نظام Rate Limiting شامل يحمي جميع نقاط API الخاصة بالمنصة من هجمات DDoS و Brute Force.

## 2. نطاق التطبيق

| النوع | الحد المسموح | نافذة زمنية | المسارات المستهدفة |
|-------|-------------|-------------|-------------------|
| العام | 60 | دقيقة | `api/*` (افتراضي) |
| المصادقة | 5 | دقيقة | `api/auth/*` |
| التسجيل | 3 | ساعة | `api/register` |
| كلمة المرور | 3 | 5 دقائق | `api/password/*` |
| Webhook | 200 | دقيقة | `api/webhooks/*` |
| الدفع | 10 | دقيقة | `api/payments/*` |
| لوحة الإدارة | 100 | دقيقة | `api/admin/*` |
| KYC | 10 | ساعة | `api/kyc/*` |
| OTP | 3 | 5 دقائق | `api/otp/*` |
| API Key (تجار) | 1000 | ساعة | `api/merchant/*` |

## 3. آلية العمل

```
الطلب → ApiRateLimiter middleware
  ├─ تصنيف الطلب (نوع + هوية)
  │   ├─ مستخدم → rate_{type}_user_{id}
  │   ├─ API Key → rate_{type}_apikey_{hash}
  │   └─ IP → rate_{type}_ip_{sanitized_ip}
  ├─ فحص Cache (RateLimiter Laravel)
  │   ├─ ضمن الحد → → عداد + تكملة
  │   └─ تجاوز الحد ← → 429 + Retry-After header
  └─ استجابة → X-RateLimit-Limit + X-RateLimit-Remaining
```

### 3.1 تدرج الهوية
1. **مستخدم مصادق** — معرف المستخدم (user_id)
2. **API Key** — hash للـ API key (للتجار/الشركاء)
3. **IP** — عنوان IP (للمستخدمين غير المصادقين)

### 3.2 الاستثناءات
- `api/health` — بدون حد
- `api/status` — بدون حد

## 4. رؤوس الاستجابة

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 42
Retry-After: 28
```

## 5. هيكل خطأ 429

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "طلبات كثيرة جداً. حاول مرة أخرى بعد X دقيقة."
  }
}
```

## 6. خطة الاختبارات

| # | السيناريو | المتوقع |
|---|-----------|---------|
| 1 | طلب عادي ضمن الحد | `200` مع `X-RateLimit-Remaining > 0` |
| 2 | تجاوز حد التسجيل (4 محاولات) | `429` RATE_LIMIT_EXCEEDED |
| 3 | تجاوز حد المصادقة (6 محاولات) | `429` |
| 4 | مستخدم مصادق — حد مختلف عن IP | حد 60 (مستخدم) vs 60 (IP) |
| 5 | API Key — حد 1000/ساعة | `X-RateLimit-Limit: 1000` |
| 6 | مسار مستثنى (health) | لا يوجد حد |

## 7. الأدوات (Laravel 12)

- `Illuminate\Cache\RateLimiter` — الكاش المدمج
- Middleware `ApiRateLimiter` — التسجيل في `Kernel.php`
- Config `config/rate-limiting.php` — إعدادات قابلة للتعديل

## 8. معايير القبول

- [ ] جميع نقاط API محمية بـ Rate Limiting
- [ ] الحدود قابلة للتعديل عبر config
- [ ] هوية الطلب تتصاعد (User > API Key > IP)
- [ ] رسالة الخطأ 429 بالعربية
- [ ] رؤوس `X-RateLimit-*` مضمنة في كل استجابة
