# M3 - بوابة الدفع وروابط الدفع

## الوصف
إنشاء روابط دفع ومعالجة مدفوعات العملاء للتاجر.

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
3. إنشاء رابط دفع: `frontend_url/pay/{reference}`
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
3. التحقق من رصيد المستخدم
4. DB::beginTransaction()
5. خصم من محفظة المستخدم
6. إضافة إلى محفظة التاجر
7. إنشاء Transaction (type: merchant_payment)
8. خصم رسوم التاجر (2.5% + 0.30 USD)
9. تحديث MerchantPayment → completed
10. DB::commit()
11. إرسال webhook للتاجر (إذا وجد)
12. Redirect إلى redirect_url مع ?success=true&ref={reference}

### API Endpoint
`POST /api/v1/merchant/payment/process/{reference}`

### قواعد العمل
- رسوم التاجر: 2.5% من المبلغ + 0.30 USD ثابت
- مبلغ الرسوم يُحتسب وقت الدفع
- webhook يُرسل للتاجر لتأكيد الدفع

## اختبارات
- إنشاء رابط دفع ← 200
- زيارة رابط الدفع ← 200
- دفع ناجح ← 302 (redirect)
- دفع برصيد غير كاف ← 400
- دفع لرابط منتهي ← 400
- webhook يُرسل للتاجر
