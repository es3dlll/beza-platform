# 15 - مواصفات API كاملة (OpenAPI)

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Disputes
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات إدارة النزاعات
    جميع الاستجابات باللغة العربية

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development

paths:
  /support/disputes:
    post:
      summary: تقديم نزاع جديد
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          multipart/form-data:
            schema:
              type: object
              required: [transaction_id, reason, description]
              properties:
                transaction_id: { type: integer }
                reason: { type: string }
                description: { type: string }
                evidence_files:
                  type: array
                  items: { type: string, format: binary }
      responses:
        '201':
          description: تم تقديم النزاع

  /admin/disputes:
    get:
      summary: قائمة النزاعات (للمشرف)
      security:
        - bearerAuth: []
        - adminAuth: []
      responses:
        '200':
          description: قائمة النزاعات المفتوحة

  /admin/disputes/{id}/resolve:
    post:
      summary: حل نزاع
      security:
        - bearerAuth: []
        - adminAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [resolution]
              properties:
                resolution:
                  type: string
                  enum: [refund, reject, partial_refund]
                partial_amount:
                  type: number
                admin_notes:
                  type: string
      responses:
        '200':
          description: تم حل النزاع
        '422':
          description: فشل الحل

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
    adminAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

## أمثلة cURL

```bash
# تقديم نزاع
curl -X POST http://localhost:8000/api/v1/support/disputes \
  -H "Authorization: Bearer user_token" \
  -F "transaction_id=42" \
  -F "reason=منتج لم يصلك" \
  -F "description=طلبت منتج من متجر الإلكترونيات ولم يصلني" \
  -F "evidence_files[]=@receipt.jpg"

# حل نزاع (refund)
curl -X POST http://localhost:8000/api/v1/admin/disputes/5/resolve \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{"resolution": "refund", "admin_notes": "تم التحقق والتأكيد"}'

# حل نزاع (رفض)
curl -X POST http://localhost:8000/api/v1/admin/disputes/5/resolve \
  -H "Authorization: Bearer admin_token" \
  -d '{"resolution": "reject", "admin_notes": "الأدلة غير كافية"}'
```
