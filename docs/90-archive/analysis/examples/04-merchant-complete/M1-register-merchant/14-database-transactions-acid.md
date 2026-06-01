# 14 - ACID + الأقفال + حالات السباق (ACID + Locks + Race Conditions)

## التسجيل المتزامن
بدون قفل: طلبان متزامنان بنفس السجل التجاري → إنشاء تاجرين مكررين.

## الحل: UNIQUE constraint + DB transaction
```php
DB::transaction(function () {
    Merchant::create([...]);  // UNIQUE يمنع التكرار على مستوى DB
    foreach ($documents as $doc) { MerchantDocument::create([...]); }
}, attempts: 3);
```

```sql
-- UNIQUE(commercial_registration) + UNIQUE(tax_id) يمنعان الإدخال المكرر
-- DB::transaction يضمن Atomicity بين التاجر والمستندات
```

## Atomicity
إذا فشل إنشاء مستند، يتم التراجع عن إنشاء التاجر بالكامل. هذا يضمن عدم وجود تاجر بدون مستندات.

## Consistency
قاعدة البيانات تفرض unique constraints و foreign keys لضمان سلامة البيانات.

## Isolation
المعاملة معزولة، مما يمنع حدوث race conditions بين طلبين متزامنين.

## Durability
بمجرد تأكيد المعاملة، تكون البيانات مخزنة بشكل دائم في MySQL.
