# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI 3.0 Specification

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات منصة Beza للمدفوعات الرقمية
    جميع الاستجابات باللغة العربية

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development

paths:
  /wallet/exchange:
    post:
      summary: صرافة بين العملات
      description: |
        تحويل أموال بين محفظة SYP و USD
        - الحد الأدنى: 1,000 SYP / 1 USD
        - الرسوم: 1.5% (هامش ربح)
        - السعر: سعر السوق
      operationId: exchangeCurrency
      tags:
        - Wallet
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ExchangeRequest'
            example:
              from_currency: "SYP"
              to_currency: "USD"
              amount: 100000.00
      responses:
        '200':
          description: تمت الصرافة بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ExchangeSuccessResponse'
        '401':
          description: غير مصادق
        '422':
          description: فشل التحقق
        '503':
          description: سعر الصرف غير متاح

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    ExchangeRequest:
      type: object
      required:
        - from_currency
        - to_currency
        - amount
      properties:
        from_currency:
          type: string
          enum: [SYP, USD]
          example: "SYP"
        to_currency:
          type: string
          enum: [SYP, USD]
          example: "USD"
        amount:
          type: number
          description: المبلغ المراد تحويله
          example: 100000.00
          minimum: 0.01
          maximum: 9999999.99

    ExchangeTransaction:
      type: object
      properties:
        id: { type: integer, example: 55 }
        reference_number: { type: string, example: "BZ270526143200A1B2C3" }
        type: { type: string, example: "exchange" }
        status: { type: string, example: "completed" }
        from_currency: { type: string, example: "SYP" }
        to_currency: { type: string, example: "USD" }
        amount: { type: number, example: 100000.00 }
        converted_amount: { type: number, example: 7.58 }
        fee: { type: number, example: 1500.00 }
        rate: { type: number, example: 13000.00 }
        fee_percentage: { type: number, example: 1.50 }
        completed_at: { type: string, format: date-time }

    ExchangeSuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: "تمت الصرافة بنجاح"
        data:
          type: object
          properties:
            transaction:
              $ref: '#/components/schemas/ExchangeTransaction'
            new_balances:
              type: object
              properties:
                syp: { type: number, example: 48500.00 }
                usd: { type: number, example: 507.58 }
```

## Postman Collection

```json
{
  "info": {
    "name": "Beza API — Exchange",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Exchange SYP→USD",
      "request": {
        "method": "POST",
        "header": [
          { "key": "Accept", "value": "application/json" },
          { "key": "Authorization", "value": "Bearer {{TOKEN}}" }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"from_currency\": \"SYP\",\n  \"to_currency\": \"USD\",\n  \"amount\": 100000\n}",
          "options": { "raw": { "language": "json" } }
        },
        "url": {
          "raw": "{{BASE_URL}}/api/v1/wallet/exchange",
          "host": ["{{BASE_URL}}"],
          "path": ["api", "v1", "wallet", "exchange"]
        }
      }
    }
  ],
  "variable": [
    { "key": "BASE_URL", "value": "http://localhost:8000" },
    { "key": "TOKEN", "value": "" }
  ]
}
```

## أمثلة cURL

### نجاح — SYP → USD
```bash
curl -X POST http://localhost:8000/api/v1/wallet/exchange \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"from_currency": "SYP", "to_currency": "USD", "amount": 100000}'
```

### نجاح — USD → SYP
```bash
curl -X POST http://localhost:8000/api/v1/wallet/exchange \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"from_currency": "USD", "to_currency": "SYP", "amount": 100}'
```

### خطأ — عملة واحدة
```bash
curl -X POST http://localhost:8000/api/v1/wallet/exchange \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"from_currency": "SYP", "to_currency": "SYP", "amount": 1000}'
```

### خطأ — أقل من الحد الأدنى
```bash
curl -X POST http://localhost:8000/api/v1/wallet/exchange \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"from_currency": "USD", "to_currency": "SYP", "amount": 0.5}'
```
