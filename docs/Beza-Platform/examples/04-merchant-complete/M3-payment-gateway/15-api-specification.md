# 15 - مواصفات API (API Specification)

```yaml
openapi: 3.0.0
info:
  title: Beza Merchant API — Payment Gateway
  version: 1.0.0
servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development
paths:
  /merchant/payment-link:
    post:
      summary: إنشاء رابط دفع
      security: [{ bearerAuth: [] }]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [amount, currency, expiry_hours]
              properties:
                amount: { type: number }
                currency: { type: string, enum: [SYP, USD] }
                description: { type: string }
                redirect_url: { type: string }
                expiry_hours: { type: integer }
      responses: { '201': { description: تم إنشاء الرابط } }
  /merchant/payment-link/{token}:
    get:
      summary: عرض رابط الدفع
      security: [{ bearerAuth: [] }]
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

```bash
curl -X POST http://localhost:8000/api/v1/merchant/payment-link \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 100, "currency": "USD", "expiry_hours": 24}'
```
