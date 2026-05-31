# QUICK_REFERENCE_OPERATORS.md

## 1. المعاملات المعلقة
```bash
# عرض جميع الحوالات المعلقة
php artisan remittance:show-pending

# عرض جميع فوترة التجار غير المدفوعة
php artisan merchant:show-unpaid

# إنهاء الحوالات منتهية الصلاحية
php artisan remittance:expire-pending
```

## 2. التنبيهات الحرجة
```bash
# عرض التنبيهات حسب الخطورة
curl -s /api/v1/compliance/alerts?severity=CRITICAL | jq

# عدد حالات الامتثال المفتوحة
curl -s /api/v1/compliance/cases?status=OPEN | jq '.total'

# مراجعة حالة امتثال محددة
curl -s /api/v1/compliance/cases/CASE-XXXXX | jq
```

## 3. مراقبة السيولة (الوكلاء)
```bash
# عرض أرصدة الوكلاء
curl -s /api/v1/agents?status=active | jq '.[] | {id, float: .float_balance, min: .minimum_float}'

# جميع الوكلاء ذوي الرصيد المنخفض
curl -s /api/v1/agents | jq '.[] | select(.float_balance < (.minimum_float * 1.2))'

# تسوية الوكيل
curl -X POST /api/v1/agents/{id}/settle -H 'Content-Type: application/json'
```

## 4. التقارير اليدوية
```bash
# إنشاء تقرير تسوية
php artisan ledger:reconcile

# إنشاء التقرير الأسبوعي
php artisan ledger:generate-weekly-report

# عرض لوحة القيادة التنفيذية
curl -s /api/v1/ledger/executive-summary | jq
```

## 5. جهات الاتصال للتصعيد
| المستوى | الشخص | الهاتف | البريد الإلكتروني |
|---------|-------|--------|-------------------|
| الدعم الفني L1 | — | — | support@beza.com |
| الامتثال | — | — | compliance@beza.com |
| إدارة العمليات | — | — | ops@beza.com |
| أمن المعلومات | — | — | security@beza.com |

## 6. أوامر سريعة لـ Artisan
```bash
# مسح جميع الذاكرات المؤقتة
php artisan cache:clear

# إعادة تحميل قواعد الامتثال
php artisan cache:forget beza_compliance:rules

# التحقق من صحة النظام
php artisan ledger:health-check
```
