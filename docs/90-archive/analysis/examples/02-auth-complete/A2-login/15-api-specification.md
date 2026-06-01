# 15 - مواصفات API (API Specification)

## OpenAPI 3.0

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Auth
  version: 1.0.0

paths:
  /auth/login:
    post:
      summary: تسجيل الدخول
      operationId: login
      tags: [Auth]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/LoginRequest'
      responses:
        '200':
          description: تم تسجيل الدخول
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/LoginSuccess'
        '401':
          description: بيانات غير صحيحة
        '403':
          description: حساب موقوف
        '429':
          description: حساب مقفل / طلبات كثيرة

  /auth/refresh:
    post:
      summary: تجديد التوكن (Token Refresh)
      operationId: refreshToken
      tags: [Auth]
      security:
        - bearerAuth: []
      responses:
        '200':
          description: تم تجديد التوكن
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/RefreshSuccess'
        '401':
          description: التوكن منتهي أو ملغي — يجب إعادة تسجيل الدخول

components:
  schemas:
    LoginRequest:
      type: object
      required: [phone, password]
      properties:
        phone:
          type: string
          example: "0999123456"
        password:
          type: string
          example: "password123"
        device_id:
          type: string
          nullable: true

    LoginSuccess:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم تسجيل الدخول بنجاح" }
        data:
          type: object
          properties:
            user:
              $ref: '#/components/schemas/UserResource'
            token:
              type: string
              example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
            requires_2fa:
              type: boolean
              example: false

    RefreshSuccess:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم تجديد التوكن" }
        data:
          type: object
          properties:
            token:
              type: string
              example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
            token_type:
              type: string
              example: "bearer"
            expires_in:
              type: integer
              example: 3600

    LoginError:
      type: object
      properties:
        success: { type: boolean, example: false }
        message: { type: string }
        errors:
          type: object
          properties:
            locked_remaining_minutes:
              type: integer
              example: 15
```

## cURL

```bash
# نجاح
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "0999123456",
    "password": "password123"
  }'

# خطأ — بيانات غير صحيحة
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "0999123456",
    "password": "wrongpassword"
  }'

# تجديد التوكن (refresh)
curl -X POST http://localhost:8000/api/v1/auth/refresh \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```
