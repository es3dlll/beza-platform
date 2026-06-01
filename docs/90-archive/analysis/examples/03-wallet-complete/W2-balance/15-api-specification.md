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
  /wallet/balance:
    get:
      summary: عرض الرصيد
      description: |
        عرض رصيد المستخدم الحالي لكل العملات (SYP + USD)
        - يتم تخزين الرصيد مؤقتاً لمدة 30 ثانية (Cache)
        - لا يحتوي Body — مجرد GET
      operationId: getBalance
      tags:
        - Wallet
      security:
        - bearerAuth: []
      responses:
        '200':
          description: تم جلب الرصيد بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/BalanceSuccessResponse'
        '401':
          description: غير مصادق — يلزم Bearer token
        '404':
          description: المحافظ غير موجودة
        '429':
          description: تجاوز حد الطلبات (60 طلب/دقيقة)

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    WalletBalance:
      type: object
      properties:
        balance:
          type: number
          description: الرصيد الكلي
          example: 150000.00
        frozen:
          type: number
          description: الرصيد المجمد
          example: 5000.00
        available:
          type: number
          description: الرصيد المتاح (balance - frozen)
          example: 145000.00
        wallet_number:
          type: string
          description: رقم المحفظة
          example: "621234567890"

    BalanceSuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        data:
          type: object
          properties:
            syp:
              $ref: '#/components/schemas/WalletBalance'
            usd:
              $ref: '#/components/schemas/WalletBalance'

    BalanceErrorResponse:
      type: object
      properties:
        success:
          type: boolean
          example: false
        message:
          type: string
          example: "لم يتم العثور على محافظ"
        errors:
          type: object
          example:
            wallets: ["المستخدم الحالي ليس لديه محافظ"]
```

## Postman Collection

```json
{
  "info": {
    "name": "Beza API — Wallet Balance",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Get Balance",
      "event": [
        {
          "listen": "test",
          "script": {
            "exec": [
              "pm.test('Status 200', function() {",
              "  pm.response.to.have.status(200);",
              "});",
              "",
              "pm.test('Balance structure', function() {",
              "  var json = pm.response.json();",
              "  pm.expect(json.success).to.eql(true);",
              "  pm.expect(json.data.syp).to.have.property('balance');",
              "  pm.expect(json.data.usd).to.have.property('balance');",
              "  pm.expect(json.data.syp).to.have.property('wallet_number');",
              "  pm.expect(json.data.usd).to.have.property('wallet_number');",
              "});"
            ]
          }
        }
      ],
      "request": {
        "method": "GET",
        "header": [
          { "key": "Accept", "value": "application/json" },
          { "key": "Authorization", "value": "Bearer {{TOKEN}}" }
        ],
        "url": {
          "raw": "{{BASE_URL}}/api/v1/wallet/balance",
          "host": ["{{BASE_URL}}"],
          "path": ["api", "v1", "wallet", "balance"]
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

### نجاح
```bash
curl -X GET http://localhost:8000/api/v1/wallet/balance \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

### غير مصادق
```bash
curl -X GET http://localhost:8000/api/v1/wallet/balance \
  -H "Accept: application/json"
```
