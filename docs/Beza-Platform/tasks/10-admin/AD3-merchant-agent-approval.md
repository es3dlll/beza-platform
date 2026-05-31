# AD3 - الموافقة على التجار والوكلاء (Admin)

## الوصف
مراجعة طلبات تسجيل التجار والوكلاء والموافقة عليها.

## قائمة الطلبات

### التجار
`GET /api/v1/admin/merchants/applications`
- فلترة: status = pending

### الوكلاء
`GET /api/v1/admin/agents/applications`
- فلترة: status = pending

## الموافقة/الرفض

### تاجر
`POST /api/v1/admin/merchants/{id}/approve`
`POST /api/v1/admin/merchants/{id}/reject` (+ reason)

### وكيل
`POST /api/v1/admin/agents/{id}/approve`
`POST /api/v1/admin/agents/{id}/reject` (+ reason)

## سير العمل (موافقة)
1. مراجعة المستندات المقدمة
2. التأكد من صحة المعلومات
3. تحديث merchant.status = 'active' (أو agent.status = 'active')
4. user.is_merchant = true (أو user.is_agent = true)
5. إشعار المستخدم بقبول الطلب

## سير العمل (رفض)
1. تحديد سبب الرفض
2. merchant.status = 'rejected'
3. إشعار المستخدم بسبب الرفض

## واجهات المستخدم
- React Admin: MerchantApplications, AgentApplications

## اختبارات
- عرض طلبات التجار ← 200
- الموافقة على تاجر ← 200 + user.is_merchant = true
- الرفض مع سبب ← 200
- محاولة الموافقة على تاجر بدون صلاحية ← 403
