# 14 - ACID + الأقفال + الـ Race Conditions

## مشكلة Race Condition في إنشاء الصفقات

إنشاء صفقة بسيط ولا يحتاج أقفالاً معقدة لأن المستخدمين لا ينشئون نفس الصفقة. لكن عند الاستثمار (D2) تحتاج أقفالاً.

## Atomicity في إنشاء الصفقة

```sql
START TRANSACTION;
INSERT INTO deals (title, target_amount, currency, ...)
VALUES ('تجارة شحنات', 50000, 'USD', ...);
COMMIT;
```

## ضمان عدم تكرار title

```php
// التحقق على مستوى Laravel + UNIQUE على مستوى DB (اختياري)
' title' => 'unique:deals,title'
```

## ملخص ACID لهذه العملية

| الخاصية | التطبيق |
|---------|---------|
| Atomicity | DB::transaction — كل شيء أو لا شيء |
| Consistency | target_amount ≥ 100, currency ∈ {SYP, USD} |
| Isolation | عملية واحدة — لا تزاحم |
| Durability | InnoDB + binlog |
