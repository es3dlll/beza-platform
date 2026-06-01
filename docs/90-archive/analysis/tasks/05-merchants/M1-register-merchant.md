# M1 - تسجيل تاجر

## الوصف
تحويل حساب مستخدم عادي إلى حساب تاجر.

## المدخلات
| الحقل | النوع |
|-------|-------|
| business_name | string |
| business_register_number | string, unique |
| tax_number | string, nullable |
| business_type | enum: individual, company, nonprofit |
| merchant_type | enum: ecommerce, physical, both |
| website | string, nullable |
| description | text, nullable |
| contact_info | json: {address, phone, email} |
| bank_account | json: {bank_name, account_number, iban} |

## سير العمل
1. إنشاء Merchant مرتبط بـ user_id
2. user.is_merchant = true
3. حالة التاجر: 'pending' (تحتاج موافقة المشرف)
4. إنشاء API key للتاجر
5. إرسال إشعار للمشرف بوجود طلب تاجر جديد

## قواعد العمل
- user يجب أن يكون مسجلاً مسبقاً
- رقم السجل التجاري يجب أن يكون فريداً
- يتم توليد API_key تلقائياً (64 حرفاً)

## جداول قاعدة البيانات
- merchants
- users (is_merchant)

## API Endpoint
`POST /api/v1/merchant/register`

## واجهات المستخدم
- React Merchant: تسجيل تاجر
- تطبيق الجوال: MerchantRegistrationScreen

## اختبارات
- تسجيل تاجر بمعلومات صحيحة ← 201
- تسجيل برقم سجل موجود ← 400
- تسجيل بدون وثائق ← 422
