# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI — KYC

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — KYC
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات التحقق من الهوية
    جميع الاستجابات باللغة العربية

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development

paths:
  /kyc/submit:
    post:
      summary: رفع وثائق KYC
      operationId: submitKyc
      tags: [KYC]
      security: [bearerAuth: []]
      requestBody:
        required: true
        content:
          multipart/form-data:
            schema:
              type: object
              required: [front_id, back_id, selfie, address_proof, doc_type]
              properties:
                front_id:
                  type: string
                  format: binary
                  description: صورة الهوية الأمامية
                back_id:
                  type: string
                  format: binary
                  description: صورة الهوية الخلفية
                selfie:
                  type: string
                  format: binary
                  description: صورة شخصية
                address_proof:
                  type: string
                  format: binary
                  description: إثبات عنوان
                doc_type:
                  type: string
                  enum: [ID, Passport, Driver_License]
      responses:
        '201':
          description: تم الرفع
        '422':
          description: خطأ في التحقق

  /kyc/status:
    get:
      summary: حالة KYC
      tags: [KYC]
      security: [bearerAuth: []]
      responses:
        '200':
          description: حالة KYC الحالية

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

## cURL

```bash
# رفع وثائق
curl -X POST http://localhost:8000/api/v1/kyc/submit \
  -H "Authorization: Bearer TOKEN" \
  -F "front_id=@/path/to/front.jpg" \
  -F "back_id=@/path/to/back.jpg" \
  -F "selfie=@/path/to/selfie.jpg" \
  -F "address_proof=@/path/to/address.pdf" \
  -F "doc_type=ID"

# حالة KYC
curl http://localhost:8000/api/v1/kyc/status \
  -H "Authorization: Bearer TOKEN"
```
