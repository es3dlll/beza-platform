# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI 3.0 Specification

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API
  version: 1.0.0

servers:
  - url: http://localhost:8000/api/v1

paths:
  /deposit/bank:
    post:
      summary: إيداع بنكي
      operationId: t4deposit-bank
      tags:
        - BankDeposit
      security:
        - bearerAuth: []
      responses:
        '201':
          description: تمت العملية بنجاح
        '422':
          description: خطأ في البيانات
        '401':
          description: غير مصادق
  /deposit/bank/status:
    get:
      summary: إيداع بنكي
      operationId: t4deposit-bank
      tags:
        - BankDeposit
      security:
        - bearerAuth: []
      responses:
        '201':
          description: تمت العملية بنجاح
        '422':
          description: خطأ في البيانات
        '401':
          description: غير مصادق

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

## أمثلة cURL

```bash
curl -X POST http://localhost:8000/deposit/bank \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100,
    "currency": "USD",
    "pin": "1234"
  }'
```

### خطأ — رصيد غير كافٍ

```bash
curl -X POST http://localhost:8000/deposit/bank \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 999999,
    "currency": "USD",
    "pin": "1234"
  }'
```
