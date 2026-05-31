# 15 - مواصفات API (API Specification)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Merchant API
  version: 1.0.0
servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development
paths:
  /merchant/register:
    post:
      summary: تسجيل تاجر جديد
      security: [{ bearerAuth: [] }]
      requestBody:
        required: true
        content:
          multipart/form-data:
            schema:
              type: object
              required: [business_name, business_type, commercial_registration, tax_id]
              properties:
                business_name: { type: string }
                business_type: { type: string }
                commercial_registration: { type: string }
                tax_id: { type: string }
                documents: { type: array, items: { type: string, format: binary } }
      responses:
        '201': { description: تم تقديم الطلب }
  /merchant/status/{id}:
    get:
      summary: حالة طلب التسجيل
      security: [{ bearerAuth: [] }]
      parameters: [{ name: id, in: path, required: true, schema: { type: integer } }]
      responses: { '200': { description: حالة الطلب } }
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

## cURL
```bash
curl -X POST http://localhost:8000/api/v1/merchant/register \
  -H "Authorization: Bearer TOKEN" \
  -F "business_name=متجر أحمد" \
  -F "business_type=electronics" \
  -F "commercial_registration=CR123456" \
  -F "tax_id=TX123456"
```
