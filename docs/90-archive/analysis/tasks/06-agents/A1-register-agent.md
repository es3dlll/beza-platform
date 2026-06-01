# AG1 - تسجيل وكيل

## الوصف
تحويل حساب مستخدم إلى وكيل (مكتب صرافة).

## المدخلات
| الحقل | النوع |
|-------|-------|
| shop_name | string |
| license_number | string, unique |
| address | string |
| city | string |
| latitude | string, nullable |
| longitude | string, nullable |

## سير العمل
1. إنشاء Agent مرتبط بـ user_id
2. user.is_agent = true
3. حالة الوكيل: 'pending' (تحتاج موافقة المشرف)
4. إشعار المشرف بوجود طلب وكيل جديد

## قواعد العمل
- رخصة الصرافة إجبارية
- الوكيل يجب أن يكون موجوداً في مدينة معينة
- commission_percent الافتراضي: 1.0%
- daily_limit الافتراضي: 10,000 USD

## جداول قاعدة البيانات
- agents
- users (is_agent)

## API Endpoint
`POST /api/v1/agent/register`

## اختبارات
- تسجيل وكيل ← 201
- تسجيل برخصة موجودة ← 400
- تسجيل بدون بيانات ← 422
