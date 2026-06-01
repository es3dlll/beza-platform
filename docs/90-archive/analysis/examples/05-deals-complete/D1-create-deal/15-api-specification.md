# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI 3.0 — Create Deal

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Deals
  version: 1.0.0
servers:
  - url: http://localhost:8000/api/v1

paths:
  /admin/deals:
    post:
      summary: إنشاء صفقة جديدة (Admin)
      operationId: createDeal
      tags: [Admin Deals]
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [title, target_amount, currency, expected_profit_percentage, duration_days, category, risk_level]
              properties:
                title:
                  type: string
                  example: "تجارة شحنات إلكترونية"
                description:
                  type: string
                  example: "استثمار في شحنة إلكترونيات من الصين"
                target_amount:
                  type: number
                  example: 50000
                currency:
                  type: string
                  enum: [SYP, USD]
                expected_profit_percentage:
                  type: number
                  example: 15.00
                duration_days:
                  type: integer
                  example: 90
                category:
                  type: string
                  example: "trade"
                risk_level:
                  type: string
                  enum: [low, medium, high]
      responses:
        '201':
          description: تم إنشاء الصفقة
        '403':
          description: غير مصرح (ليس Admin)

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
```

## cURL

```bash
curl -X POST http://localhost:8000/api/v1/admin/deals \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "تجارة شحنات إلكترونية",
    "description": "استثمار في شحنة إلكترونيات",
    "target_amount": 50000,
    "currency": "USD",
    "expected_profit_percentage": 15,
    "duration_days": 90,
    "category": "trade",
    "risk_level": "medium"
  }'
```
