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
  /transfer:
    post:
      summary: تحويل P2P بين المستخدمين
      description: |
        تحويل أموال من محفظة المستخدم الحالي إلى محفظة مستخدم آخر
        - الرسوم: 0% (مجاني)
        - الحد اليومي: 2,000 USD / 2,000,000 SYP
        - يتطلب PIN تأكيد
      operationId: transferP2P
      tags:
        - Transfer
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/TransferRequest'
            example:
              to_phone: "963944654321"
              amount: 100.00
              currency: "USD"
              pin: "1234"
              description: "مصروف أخوي"
      responses:
        '201':
          description: تم التحويل بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/TransferSuccessResponse'
        '400':
          description: طلب غير صحيح
        '401':
          description: غير مصادق — يلزم Bearer token
        '404':
          description: المستلم غير موجود
        '422':
          description: فشل التحقق من البيانات أو رصيد غير كافٍ
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/TransferErrorResponse'
        '429':
          description: تجاوز حد الطلبات (30 طلب/دقيقة)

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    TransferRequest:
      type: object
      required:
        - to_phone
        - amount
        - currency
        - pin
      properties:
        to_phone:
          type: string
          description: رقم هاتف المستلم (يجب أن يكون مسجلاً ونشطاً)
          example: "963944654321"
          pattern: '^[0-9+\-\(\)\s]{7,20}$'
        amount:
          type: number
          description: مبلغ التحويل (أقل قيمة 1)
          example: 100.00
          minimum: 1
          maximum: 9999999.99
        currency:
          type: string
          description: العملة
          enum: [SYP, USD]
          example: "USD"
        pin:
          type: string
          description: رمز PIN للمصادقة (4 أرقام)
          example: "1234"
          minLength: 4
          maxLength: 4
        description:
          type: string
          description: وصف التحويل (اختياري)
          example: "مصروف أخوي"
          maxLength: 255

    TransactionResource:
      type: object
      properties:
        id:
          type: integer
          example: 42
        reference_number:
          type: string
          example: "BZ260527143200A1B2C3"
        type:
          type: string
          enum: [transfer]
          example: "transfer"
        status:
          type: string
          enum: [completed]
          example: "completed"
        amount:
          type: number
          example: 100.00
        amount_in_usd:
          type: number
          example: 100.00
        currency:
          type: string
          example: "USD"
        fee:
          type: number
          example: 0.00
        description:
          type: string
          nullable: true
          example: "مصروف أخوي"
        sender:
          type: object
          properties:
            id: { type: integer, example: 1 }
            name: { type: string, example: "أحمد" }
            phone: { type: string, example: "963944123456" }
        receiver:
          type: object
          properties:
            id: { type: integer, example: 2 }
            name: { type: string, example: "محمد" }
            phone: { type: string, example: "963944654321" }
        created_at:
          type: string
          format: date-time
          example: "2026-05-27T14:32:00+03:00"
        completed_at:
          type: string
          format: date-time
          nullable: true
          example: "2026-05-27T14:32:00+03:00"

    TransferSuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: "تم التحويل بنجاح"
        data:
          type: object
          properties:
            transaction:
              $ref: '#/components/schemas/TransactionResource'
            new_balance:
              type: number
              example: 400.00

    TransferErrorResponse:
      type: object
      properties:
        success:
          type: boolean
          example: false
        message:
          type: string
          example: "رصيد غير كافٍ"
        errors:
          type: object
          additionalProperties:
            type: array
            items:
              type: string
          example:
            balance: ["رصيد المحفظة غير كافٍ لإتمام العملية"]
```

## Postman Collection (JSON Key)

```json
{
  "info": {
    "name": "Beza API — Transfer",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "P2P Transfer",
      "event": [
        {
          "listen": "test",
          "script": {
            "exec": [
              "pm.test('Status 201', function() {",
              "  pm.response.to.have.status(201);",
              "});",
              "",
              "pm.test('Success message', function() {",
              "  var json = pm.response.json();",
              "  pm.expect(json.success).to.eql(true);",
              "  pm.expect(json.message).to.eql('تم التحويل بنجاح');",
              "});",
              "",
              "pm.test('Transaction data', function() {",
              "  var json = pm.response.json();",
              "  pm.expect(json.data.transaction.reference_number).to.match(/^BZ/);",
              "  pm.expect(json.data.transaction.status).to.eql('completed');",
              "});"
            ]
          }
        }
      ],
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Accept",
            "value": "application/json"
          },
          {
            "key": "Authorization",
            "value": "Bearer {{TOKEN}}"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"to_phone\": \"963944654321\",\n  \"amount\": 100,\n  \"currency\": \"USD\",\n  \"pin\": \"1234\",\n  \"description\": \"مصروف أخوي\"\n}",
          "options": {
            "raw": {
              "language": "json"
            }
          }
        },
        "url": {
          "raw": "{{BASE_URL}}/api/v1/transfer",
          "host": ["{{BASE_URL}}"],
          "path": ["api", "v1", "transfer"]
        }
      }
    }
  ],
  "variable": [
    {
      "key": "BASE_URL",
      "value": "http://localhost:8000"
    },
    {
      "key": "TOKEN",
      "value": ""
    }
  ]
}
```

## أمثلة cURL

### نجاح
```bash
curl -X POST http://localhost:8000/api/v1/transfer \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "to_phone": "963944654321",
    "amount": 100,
    "currency": "USD",
    "pin": "1234",
    "description": "مصروف أخوي"
  }'
```

### خطأ — رصيد غير كافٍ
```bash
curl -X POST http://localhost:8000/api/v1/transfer \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "to_phone": "963944654321",
    "amount": 999999,
    "currency": "USD",
    "pin": "1234"
  }'
```

### خطأ — PIN غير صحيح
```bash
curl -X POST http://localhost:8000/api/v1/transfer \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "to_phone": "963944654321",
    "amount": 50,
    "currency": "USD",
    "pin": "0000"
  }'
```
