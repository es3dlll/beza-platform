# 15 - مواصفات API (تلقائي — لا يحتاج API منفصل)

## عملية إنشاء المحفظة هي Event ذاتي، وليس API

لا يوجد API مخصص لإنشاء المحفظة. يتم إنشاؤها تلقائياً عند تسجيل المستخدم.

## API التسجيل الذي يطلق العملية

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات منصة Beza للمدفوعات الرقمية
    إنشاء المحفظة يتم تلقائياً مع التسجيل

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development

paths:
  /register:
    post:
      summary: تسجيل مستخدم جديد
      description: |
        إنشاء حساب جديد مع محفظتين (SYP + USD) تلقائياً
        - هدية 5$ لمحفظة USD
      operationId: registerUser
      tags:
        - Auth
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/RegisterRequest'
            example:
              name: "أحمد"
              phone: "963944123456"
              password: "password123"
              pin_code: "1234"
      responses:
        '201':
          description: تم التسجيل بنجاح مع المحافظ
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/RegisterSuccessResponse'
        '422':
          description: بيانات غير صحيحة

components:
  schemas:
    RegisterRequest:
      type: object
      required:
        - name
        - phone
        - password
        - pin_code
      properties:
        name:
          type: string
          description: اسم المستخدم
          example: "أحمد"
        phone:
          type: string
          description: رقم الهاتف
          example: "963944123456"
          pattern: '^[0-9+\-\(\)\s]{7,20}$'
        password:
          type: string
          description: كلمة المرور
          example: "password123"
          minLength: 8
        pin_code:
          type: string
          description: رمز PIN (4 أرقام)
          example: "1234"
          minLength: 4
          maxLength: 4
        fcm_token:
          type: string
          description: FCM token للإشعارات (اختياري)

    WalletData:
      type: object
      properties:
        wallet_number:
          type: string
          example: "621234567890"
        balance:
          type: number
          example: 0.00
        currency:
          type: string
          example: "SYP"

    RegisterSuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: "تم التسجيل بنجاح"
        data:
          type: object
          properties:
            user:
              type: object
              properties:
                id: { type: integer }
                name: { type: string }
                phone: { type: string }
                status: { type: string }
            token:
              type: string
              example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
            wallets:
              type: object
              properties:
                syp:
                  $ref: '#/components/schemas/WalletData'
                usd:
                  $ref: '#/components/schemas/WalletData'
```

## Postman Collection

```json
{
  "info": {
    "name": "Beza API — Register + Auto Wallet",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Register User",
      "request": {
        "method": "POST",
        "header": [
          { "key": "Accept", "value": "application/json" }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"name\": \"أحمد\",\n  \"phone\": \"963944123456\",\n  \"password\": \"password123\",\n  \"pin_code\": \"1234\"\n}",
          "options": {
            "raw": { "language": "json" }
          }
        },
        "url": {
          "raw": "{{BASE_URL}}/api/v1/register",
          "host": ["{{BASE_URL}}"],
          "path": ["api", "v1", "register"]
        }
      }
    }
  ],
  "variable": [
    { "key": "BASE_URL", "value": "http://localhost:8000" }
  ]
}
```

## أمثلة cURL

### نجاح — تسجيل + إنشاء المحافظ تلقائياً
```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد",
    "phone": "963944123456",
    "password": "password123",
    "pin_code": "1234"
  }'
```

### فشل — رقم هاتف مكرر
```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد",
    "phone": "963944123456",
    "password": "password123",
    "pin_code": "1234"
  }'
```
