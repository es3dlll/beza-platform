# AD5 - إدارة النزاعات (Disputes)

## الوصف
مراجعة وحل النزاعات بين المستخدمين أو مع التجار.

## إنشاء نزاع (من المستخدم)
`POST /api/v1/support/disputes`

### المدخلات
| الحقل | النوع |
|-------|-------|
| transaction_id | id |
| reason | string |
| description | text |
| evidence_files | array of files |

## قائمة النزاعات (Admin)
`GET /api/v1/admin/disputes`
- فلترة: status (open, investigating, resolved)

## حل النزاع (Admin)
`POST /api/v1/admin/disputes/{id}/resolve`

### الإجراءات
1. مراجعة التفاصيل والأدلة
2. قرار المشرف:
   - refund (استرجاع المبلغ للعميل)
   - reject (رفض النزاع)
   - partial_refund (استرجاع جزئي)
3. تحديث transaction.status (إذا كان استرجاع)
4. إشعار الطرفين

## API Endpoint
`POST /api/v1/admin/disputes/{id}/resolve`

## قواعد العمل
- النزاع مفتوح لمدة 7 أيام
- بعد 7 أيام دون رد: يغلق تلقائياً لصاحب الشكوى
- الاسترجاع: يُخصم من محفظة التاجر ويُضاف للعميل

## اختبارات
- إنشاء نزاع ← 201
- عرض النزاعات (Admin) ← 200
- حل النزاع باسترجاع ← تحديث الرصيد
- رفض النزاع ← بدون تغيير رصيد
