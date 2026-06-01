# D1 - إنشاء صفقة استثمارية (Admin)

## الوصف
إدراج صفقة تمويل تجاري جديدة من قبل فريق الإدارة.

## المدخلات
| الحقل | النوع |
|-------|-------|
| title | string |
| description | text |
| target_amount | decimal |
| currency | enum: SYP, USD |
| expected_profit_percent | decimal |
| duration_days | integer |
| merchant_name | string, nullable |
| merchant_info | string, nullable |
| image | file, nullable |
| documents | JSON, nullable |

## سير العمل
1. إنشاء Deal (status: open)
2. الصفقة تصبح متاحة للمستثمرين

## قواعد العمل
- target_amount هو إجمالي المبلغ المطلوب للصفقة
- expected_profit_percent هو الربح المتوقع (مثال: 8%)
- duration_days هي مدة الصفقة (مثال: 45)
- الصفقة تبقى 'open' حتى اكتمال target_amount

## جداول قاعدة البيانات
- deals

## API Endpoint
`POST /api/v1/admin/deals`

## اختبارات
- إنشاء صفقة ← 201
- إنشاء بدون عنوان ← 422
- إنشاء بمبلغ مستهدف صفر ← 422
