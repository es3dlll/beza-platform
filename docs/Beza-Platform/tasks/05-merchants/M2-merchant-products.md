# M2 - إدارة منتجات التاجر

## الوصف
إضافة، تعديل، حذف، وعرض منتجات التاجر.

## إنشاء منتج

### المدخلات
| الحقل | النوع |
|-------|-------|
| name | string |
| description | text, nullable |
| price | decimal |
| currency | enum: SYP, USD |
| stock | integer |
| image | file, nullable |
| variants | json, nullable |

### API Endpoint
`POST /api/v1/merchant/products`

## تعديل منتج

### API Endpoint
`PUT /api/v1/merchant/products/{id}`

## حذف منتج

### API Endpoint
`DELETE /api/v1/merchant/products/{id}`

## عرض المنتجات

### API Endpoint
`GET /api/v1/merchant/products`

## قواعد العمل
- المنتجات مرتبطة بـ merchant_id
- slug يتم توليده تلقائياً من name
- حذف المنتج يتطلب عدم وجود طلبات نشطة عليه

## واجهات المستخدم
- React Merchant: ProductList, AddProduct, EditProduct

## اختبارات
- إنشاء منتج ← 201
- تعديل منتج ← 200
- حذف منتج ← 200
- عرض المنتجات ← 200
- إنشاء منتج بدون اسم ← 422
