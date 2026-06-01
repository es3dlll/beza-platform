# 15 - مواصفات API (API Specification)

## OpenAPI 3.0

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Auth
  version: 1.0.0

paths:
  /auth/register:
    post:
      summary: تسجيل مستخدم جديد
      operationId: register
      tags: [Auth]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/RegisterRequest'
      responses:
        '201':
          description: تم التسجيل بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/RegisterSuccess'
        '422':
          description: بيانات غير صحيحة
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ValidationErrorResponse'
        '429':
          description: طلبات كثيرة
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/TooManyRequestsErrorResponse'

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
  schemas:
    RegisterRequest:
      type: object
      required: [name, phone, password, password_confirmation, pin_code, pin_code_confirmation]
      properties:
        name:
          type: string
          maxLength: 255
          example: "علي أحمد"
        phone:
          type: string
          pattern: '^09[0-9]{8}$'
          example: "0999123456"
        password:
          type: string
          minLength: 8
          example: "password123"
        password_confirmation:
          type: string
        pin_code:
          type: string
          minLength: 4
          maxLength: 4
          example: "1234"
        pin_code_confirmation:
          type: string
        device_id:
          type: string
          nullable: true

    RegisterSuccess:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم التسجيل بنجاح" }
        data:
          type: object
          properties:
            user:
              $ref: '#/components/schemas/UserResource'
            wallets:
              type: array
              items:
                $ref: '#/components/schemas/WalletResource'
            token:
              type: string
              example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

    UserResource:
      type: object
      properties:
        id: { type: integer }
        uuid: { type: string }
        name: { type: string }
        phone: { type: string }
        status: { type: string, enum: [pending, active, suspended] }
        kyc_status: { type: string, enum: [not_submitted, pending, verified, rejected] }

    WalletResource:
      type: object
      properties:
        id: { type: integer }
        currency: { type: string, enum: [SYP, USD] }
        balance: { type: number, example: 5.00 }
        number: { type: string }

    ValidationErrorResponse:
      type: object
      properties:
        success: { type: boolean, example: false }
        message: { type: string, example: "بيانات غير صحيحة" }
        errors:
          type: object
          additionalProperties:
            type: array
            items: { type: string }
          example:
            phone: ["رقم الهاتف مستخدم مسبقاً"]
            name: ["حقل الاسم مطلوب"]

    TooManyRequestsErrorResponse:
      type: object
      properties:
        success: { type: boolean, example: false }
        message: { type: string, example: "طلبات كثيرة جداً. حاول بعد دقيقة" }
```

## cURL

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "علي أحمد",
    "phone": "0999123456",
    "password": "password123",
    "password_confirmation": "password123",
    "pin_code": "1234",
    "pin_code_confirmation": "1234"
  }'
```
