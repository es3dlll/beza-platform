# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI — Referral

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Referral
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات الإحالة والمكافآت
    جميع الاستجابات باللغة العربية

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development

paths:
  /referral/code:
    post:
      summary: إنشاء كود إحالة
      operationId: generateReferralCode
      tags: [Referral]
      security: [bearerAuth: []]
      responses:
        '200':
          description: كود الإحالة
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean }
                  data:
                    type: object
                    properties:
                      code:
                        type: object
                        properties:
                          code: { type: string, example: "ABC12345" }
                          usage_count: { type: integer }

  /referral/claim:
    post:
      summary: تسجيل بدعوة
      operationId: claimReferral
      tags: [Referral]
      security: [bearerAuth: []]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [code]
              properties:
                code:
                  type: string
                  example: "ABC12345"
      responses:
        '200':
          description: تم تسجيل الدعوة

  /referral/rewards:
    get:
      summary: مكافآتي من الإحالات
      tags: [Referral]
      security: [bearerAuth: []]
      responses:
        '200':
          description: قائمة المكافآت

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

## cURL

```bash
# إنشاء كود
curl -X POST http://localhost:8000/api/v1/referral/code \
  -H "Authorization: Bearer TOKEN"

# تسجيل بدعوة
curl -X POST http://localhost:8000/api/v1/referral/claim \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"code": "ABC12345"}'

# مكافآتي
curl http://localhost:8000/api/v1/referral/rewards \
  -H "Authorization: Bearer TOKEN"
```
