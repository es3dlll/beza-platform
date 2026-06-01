# SEC-001: توقيع Webhooks بتقنية HMAC-SHA256

**المعرف:** `SEC-001-WH`  
**الأولوية:** 🔴 P0  
**الوكيل المنفذ:** ⚙️ Backend  
**الحالة:** تصميم

---

## 1. الوصف

نظام توقيع للـ Webhooks الواردة من/إلى المنصة لمنع هجمات التزوير (Replay & Forgery).

## 2. مواصفات التوقيع

| الخاصية | القيمة |
|---------|--------|
| الخوارزمية | `HMAC-SHA256` |
| رأس التوقيع | `X-Beza-Signature` |
| نافذة الصلاحية | ±300 ثانية (5 دقائق) |
| طول Nonce | 32 حرفاً عشوائياً |
| الصيغة | `{timestamp}.{nonce}.{signature}` |

## 3. آلية العمل

```
المرسل:
  payload = JSON body
  timestamp = now()
  nonce = random(32)
  signed = timestamp + "." + nonce + "." + payload
  signature = HMAC-SHA256(signed, secret)
  header = timestamp + "." + nonce + "." + signature
  ← X-Beza-Signature: {header}

المستقبل (Middleware WebhookSignature):
  1. استخراج signature header
  2. تحليل الأجزاء timestamp.nonce.signature
  3. التحقق من timestamp ضمن ±5 دقائق
  4. إعادة حساب HMAC-SHA256
  5. مقارنة بـ hash_equals()
  6. → تطابق: complete | عدم تطابق: 401
```

## 4. هيكل الخطأ

```json
{
  "success": false,
  "error": {
    "code": "MISSING_SIGNATURE",
    "message": "التوقيع مفقود. يرجى تضمين X-Beza-Signature في الطلب."
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "INVALID_SIGNATURE",
    "message": "التوقيع غير صالح. قد تكون الرسالة قد تم العبث بها."
  }
}
```

## 5. الإعدادات (config/services.php)

```php
'webhook' => [
    'secret' => env('WEBHOOK_SECRET'),
    'tolerance' => 300,
    'algorithm' => 'sha256',
],
```

## 6. توليد secret للتوقيع

```bash
php artisan generate:webhook-secret
# يولد: beza_wh_{random_64_hex}
```

## 7. خطة الاختبارات

| # | السيناريو | المتوقع |
|---|-----------|---------|
| 1 | توقيع صحيح لـ payload | `verify() === true` |
| 2 | تعديل payload بعد التوقيع | `verify() === false` |
| 3 | توقيع منتهي الصلاحية (>5 دقائق) | `verify() === false` |
| 4 | توقيع معدّل (tampered signature) | `verify() === false` |
| 5 | رأس توقيع مفقود | `401 MISSING_SIGNATURE` |
| 6 | رأس توقيع بتنسيق خاطئ | `verify() === false` |
| 7 | secrets مختلفة → توقيعات مختلفة | عدم تطابق |
| 8 | نفس الـ payload → nonce مختلف → Signature مختلف | فريد لكل مرة |

## 8. معايير القبول

- [ ] كل webhook يحتوي `X-Beza-Signature` ويُتحقق منه
- [ ] نافذة زمنية ±5 دقائق لمنع هجمات Replay
- [ ] Nonce عشوائي لكل طلب
- [ ] مقارنة بـ `hash_equals()` لمنع timing attack
- [ ] secret قابل للتعديل عبر `.env`
