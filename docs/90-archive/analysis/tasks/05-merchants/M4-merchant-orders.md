# M4 - إدارة طلبات التاجر

## الوصف
إدارة طلبات الشراء من المتجر الإلكتروني.

## إنشاء طلب (من قبل العميل)

### المدخلات
| الحقل | النوع |
|-------|-------|
| merchant_id | id |
| items | array: [{product_id, name, quantity, price}] |
| shipping_address | json |
| currency | enum: SYP, USD |

### سير العمل
1. حساب total_amount (items مجموع)
2. إضافة tax إذا وجد
3. إضافة shipping_fee
4. إنشاء Order (status: pending)

## تحديث حالة الطلب (من قبل التاجر)

### المسارات
- `PUT /api/v1/merchant/orders/{id}/processing` ← قيد التجهيز
- `PUT /api/v1/merchant/orders/{id}/shipped` ← تم الشحن
- `PUT /api/v1/merchant/orders/{id}/delivered` ← تم التوصيل
- `PUT /api/v1/merchant/orders/{id}/cancelled` ← إلغاء
- `PUT /api/v1/merchant/orders/{id}/refunded` ← استرجاع

## قواعد العمل
- ترتيب الحالات: pending → paid → processing → shipped → delivered
- يمكن الإلغاء فقط قبل الشحن
- الاسترجاع يتطلب أن يكون مدفوعاً

## واجهات المستخدم
- React Merchant: OrderList, OrderDetails
- Flutter (for merchants): OrdersScreen

## اختبارات
- إنشاء طلب ← 201
- عرض طلبات التاجر ← 200
- تحديث إلى processing ← 200
- إلغاء طلب بعد الشحن ← 400
