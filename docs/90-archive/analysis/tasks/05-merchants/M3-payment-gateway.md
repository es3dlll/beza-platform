# M3 - بوابة الدفع وروابط الدفع

## الوصف
إنشاء روابط دفع ومعالجة مدفوعات العملاء للتاجر. هذه المهمة هي جوهر نظام مدفوعات التجار في Beza، حيث تتيح للتاجر إنشاء روابط دفع مخصصة ومشاركتها مع العملاء لتحصيل المدفوعات عبر محافظ Beza.

---

## مواصفات نقطة الإنشاء

### POST /api/v1/merchant/payment/create

إنشاء رابط دفع جديد يرتبط بمنتج أو طلب محدد. يتطلب صلاحية تاجر (Bearer Token).

#### المدخلات

| الحقل | النوع | الإلزام | القيمة الافتراضية | الوصف |
|-------|-------|--------|-------------------|-------|
| `amount` | decimal(12,2) | مطلوب | — | مبلغ الدفع (بالفلس لأصغر وحدة عملة) |
| `currency` | enum(SYP, USD) | مطلوب | SYP | عملة الدفع |
| `order_id` | string(64) | مطلوب | — | معرف الطلب الفريد لدى التاجر |
| `customer_name` | string(128) | اختياري | null | اسم المشتري (للعرض على صفحة الدفع) |
| `customer_email` | email | اختياري | null | البريد الإلكتروني للمشتري (لإرسال الإيصال) |
| `redirect_url` | url | اختياري | merhcant dashboard | رابط إعادة التوجيه بعد الدفع |
| `webhook_url` | url | اختياري | null | رابط إشعار التاجر بنتيجة الدفع |
| `expires_in` | integer(دقائق) | اختياري | 30 | مدة صلاحية رابط الدفع |
| `description` | string(255) | اختياري | null | وصف عملية الدفع (يظهر للمشتري) |
| `metadata` | JSON | اختياري | null | بيانات إضافية (خصومات، كوبونات، إلخ) |

#### الاستجابة (200 Success)

```json
{
  "success": true,
  "data": {
    "payment_id": "pay_a1b2c3d4e5f6",
    "reference": "BZA-MERCH-20260531-A1B2C3",
    "status": "pending",
    "amount": 1500.00,
    "currency": "USD",
    "fee": 37.80,
    "net_amount": 1462.20,
    "payment_url": "https://pay.beza.com/pay/BZA-MERCH-20260531-A1B2C3",
    "expires_at": "2026-05-31T14:30:00Z",
    "created_at": "2026-05-31T14:00:00Z"
  }
}
```

#### رموز الحالة

| الرمز | المعنى |
|-------|--------|
| 200 | نجاح — تم إنشاء رابط الدفع |
| 400 | خطأ في المدخلات — حقل مطلوب ناقص أو قيمة غير صالحة |
| 401 | غير مصرح — التوكن منتهي أو غير صالح |
| 403 | ممنوع — الحساب ليس تاجراً أو غير مفعل |
| 422 | كيان غير قابل للمعالجة — duplicate order_id أو تجاوز الحد |
| 429 | تجاوز المعدل — أكثر من 100 طلب/دقيقة |

#### مثال طلب ناجح (cURL)

```
curl -X POST https://api.beza.com/api/v1/merchant/payment/create \
  -H "Authorization: Bearer {merchant_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1500.00,
    "currency": "USD",
    "order_id": "ORD-20260531-0042",
    "customer_name": "أحمد خالد",
    "customer_email": "ahmed@example.com",
    "redirect_url": "https://store.example.com/order/ORD-20260531-0042/success",
    "webhook_url": "https://store.example.com/webhooks/beza-payment",
    "description": "طلب رقم ORD-20260531-0042 - تيشيرت قطني × 2"
  }'
```

#### مثال استجابة فشل — رصيد غير كاف

```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "رصيد المحفظة غير كافٍ لإتمام هذه المعاملة",
    "details": {
      "required": 1500.00,
      "available": 420.50,
      "currency": "USD",
      "wallet_id": "wallet_u12345"
    }
  }
}
```

---

## إنشاء رابط دفع

### المدخلات
| الحقل | النوع |
|-------|-------|
| amount | decimal |
| currency | enum: SYP, USD |
| order_id | string |
| customer_name | string, nullable |
| customer_email | email, nullable |
| redirect_url | url (بعد الدفع) |
| webhook_url | url, nullable (إشعار التاجر) |

### سير العمل
1. إنشاء MerchantPayment (status: pending)
2. توليد reference فريد
3. إنشاء رابط دفع: `pay.beza.com/pay/{reference}`
4. Response: payment_url, reference

### API Endpoint
`POST /api/v1/merchant/payment/create`

## معالجة الدفع

### المدخلات
| الحقل | النوع |
|-------|-------|
| reference | string (من رابط الدفع) |

### سير العمل
1. البحث عن MerchantPayment بـ reference
2. التحقق من أن الحالة pending
3. التحقق من عدم انتهاء الصلاحية
4. التحقق من رصيد المستخدم
5. DB::beginTransaction()
6. خصم من محفظة المستخدم
7. إضافة إلى محفظة التاجر
8. إنشاء Transaction (type: merchant_payment)
9. خصم رسوم التاجر (2.5% + 0.30 USD)
10. تحديث MerchantPayment -> completed
11. DB::commit()
12. إرسال webhook للتاجر (إذا وجد)
13. Redirect إلى redirect_url مع ?success=true&ref={reference}

### API Endpoint
`POST /api/v1/merchant/payment/process/{reference}`

### قواعد العمل
- رسوم التاجر: 2.5% من المبلغ + 0.30 USD ثابت
- مبلغ الرسوم يُحتسب وقت الدفع
- webhook يُرسل للتاجر لتأكيد الدفع
- مدة صلاحية رابط الدفع الافتراضية: 30 دقيقة
- أقصى مدة صلاحية: 24 ساعة

---

## الربط التقني

| العنصر | التفاصيل |
|--------|---------|
| الوحدة المسؤولة | Merchant Module — `app/Modules/Merchant/` |
| نقطة API للإنشاء | `POST /api/v1/merchant/payment/create` — `MerchantPaymentController@create` |
| نقطة API للمعالجة | `POST /api/v1/merchant/payment/process/{reference}` — `MerchantPaymentController@process` |
| جدول قاعدة البيانات | `merchant_payments` — الحقول: id, merchant_id, order_id, amount, currency, fee, net_amount, reference(unique), status(pending/completed/failed/expired/refunded), redirect_url, webhook_url, expires_at, metadata, created_at, completed_at |
| جدول المعاملات | `transactions` — type=merchant_payment، يربط بـ merchant_payment.id |
| النماذج المرتبطة | MerchantPayment (Model), Transaction (Model), Wallet (Model) |
| الأحداث النظامية | `MerchantPaymentCreated` — يسجل في audit log |
| الأحداث النظامية | `MerchantPaymentCompleted` — يرسل webhook + إشعار للتاجر |
| الأحداث النظامية | `MerchantPaymentFailed` — يسجل سبب الفشل |
| الخدمات المطلوبة | `MerchantPaymentService` — منطق الإنشاء والمعالجة |
| الخدمات المطلوبة | `FeeCalculatorService` — حساب رسوم التاجر |
| الخدمات المطلوبة | `WebhookService` — إرسال إشعارات webhook |
| Middleware | `auth:api` + `role:merchant` + `throttle:100,1` |
| اختبارات التكامل | `MerchantPaymentTest` — إنشاء رابط + دفع ناجح + فشل رصيد + انتهاء صلاحية + webhook |
| اختبارات الأداء | k6: 500 معاملة/ثانية، استجابة < 500ms p95 |

---

## اختبارات
- إنشاء رابط دفع -> 200
- إنشاء رابط بدون amount -> 400
- إنشاء رابط بتوكن غير تاجر -> 403
- زيارة رابط الدفع -> 200
- دفع ناجح -> 302 (redirect) مع webhook
- دفع برصيد غير كاف -> 400
- دفع لرابط منتهي -> 400
- دفع لرابط مكرر (double-spend) -> 400
- webhook يُرسل للتاجر مع التوقيع
- duplicate order_id -> 422
