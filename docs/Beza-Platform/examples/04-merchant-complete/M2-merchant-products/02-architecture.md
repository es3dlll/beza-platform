# 02 - البنية المعمارية (Architecture) - منتجات التاجر (Merchant Products)

## موقع العملية في الأرشيتيكشر

```
  Flutter/React
       │ CRUD /api/v1/merchant/products
       ▼
  ┌─────────────────────────┐
  │  MerchantProductController │
  │  index / store / show /    │
  │  update / destroy          │
  └──────────┬────────────────┘
             │
  ┌──────────┴────────────────┐
  │  ProductService             │
  │  - CRUD operations          │
  │  - Ownership check          │
  └──────────┬────────────────┘
             │
  ┌──────────┴────────────────┐
  │  ProductImageService       │
  │  - Upload/Delete images    │
  └──────────┬────────────────┘
             │
        ┌────┴────┐
        │  MySQL  │
        │ products│
        │ images  │
        └─────────┘
```

## شرح الطبقات (Layers)

### طبقة التحكم (Controller Layer)
تستقبل الطلبات من الواجهة الأمامية وتوجهها إلى طبقة الخدمة. تتأكد من صحة البيانات عبر Form Request قبل تمريرها.

### طبقة الخدمة (Service Layer)
ProductService يحتوي على منطق الأعمال الخاص بالمنتجات، بينما ProductImageService يتعامل مع رفع وحذف الصور. هذه الفصل بين المسؤوليات يجعل الكود أكثر قابلية للصيانة والاختبار.

### طبقة البيانات (Data Layer)
MySQL يقوم بتخزين بيانات المنتجات والصور، مع علاقات معرفة عبر مفاتيح خارجية لضمان سلامة البيانات.

الأرشيتيكشر تتبع نمط Service Layer Pattern الذي يفصل منطق الأعمال عن طبقة التحكم، مما يسهل إعادة استخدام الكود واختباره.
