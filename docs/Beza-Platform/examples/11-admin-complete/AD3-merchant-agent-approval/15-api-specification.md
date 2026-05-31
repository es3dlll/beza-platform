# 15 - مواصفات API كاملة (OpenAPI)

```yaml
openapi: 3.0.0
info:
  title: Beza Admin API — Merchant/Agent Approval
  version: 1.0.0

paths:
  /admin/merchants/applications:
    get:
      summary: قائمة طلبات التجار المعلقة
      responses:
        '200':
          description: قائمة الطلبات
        '403':
          description: غير مصرح

  /admin/merchants/applications/{id}:
    get:
      summary: تفاصيل طلب تاجر
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200':
          description: تفاصيل الطلب مع المستندات

  /admin/merchants/{id}/approve:
    post:
      summary: الموافقة على تاجر
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200':
          description: تمت الموافقة
        '422':
          description: فشل — الطلب ليس pending أو KYC غير مكتمل

  /admin/merchants/{id}/reject:
    post:
      summary: رفض طلب تاجر
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [reason]
              properties:
                reason:
                  type: string
                  minLength: 10
                notes:
                  type: string
      responses:
        '200':
          description: تم الرفض

  /admin/agents/applications:
    get:
      summary: قائمة طلبات الوكلاء المعلقة

  /admin/agents/{id}/approve:
    post:
      summary: الموافقة على وكيل

  /admin/agents/{id}/reject:
    post:
      summary: رفض طلب وكيل
```

## أمثلة cURL

```bash
# قائمة طلبات التجار
curl http://localhost:8000/api/v1/admin/merchants/applications \
  -H "Authorization: Bearer admin_token"

# الموافقة على تاجر
curl -X POST http://localhost:8000/api/v1/admin/merchants/5/approve \
  -H "Authorization: Bearer admin_token"

# رفض تاجر مع سبب
curl -X POST http://localhost:8000/api/v1/admin/merchants/5/reject \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{"reason": "المستندات المرفوعة غير مكتملة. يرجى إرفاق السجل التجاري والتوقيع الإلكتروني."}'
```
