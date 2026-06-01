# 15 - مواصفات API (API Specification)

## OpenAPI 3.0

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Auth
  version: 1.0.0

paths:
  /auth/request-otp:
    post:
      summary: طلب رمز تحقق OTP
      operationId: requestOtp
      tags: [Auth]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/RequestOtpRequest'
      responses:
        '200':
          description: تم إرسال الرمز
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/RequestOtpSuccess'
        '422':
          description: بيانات غير صحيحة
        '429':
          description: طلبات كثيرة

  /auth/verify-otp:
    post:
      summary: التحقق من رمز OTP
      operationId: verifyOtp
      tags: [Auth]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/VerifyOtpRequest'
      responses:
        '200':
          description: تم التحقق بنجاح
        '422':
          description: رمز خاطئ أو منتهي
        '429':
          description: تجاوزت عدد المحاولات

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
  schemas:
    RequestOtpRequest:
      type: object
      required: [phone]
      properties:
        phone:
          type: string
          example: "0999123456"

    RequestOtpSuccess:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم إرسال رمز التحقق" }
        data:
          type: object
          properties:
            expires_in:
              type: integer
              example: 300
            otp:
              type: string
              description: "فقط في بيئة التطوير"
              example: "123456"

    VerifyOtpRequest:
      type: object
      required: [phone, otp]
      properties:
        phone:
          type: string
          example: "0999123456"
        otp:
          type: string
          minLength: 6
          maxLength: 6
          example: "123456"
```

## cURL

```bash
# طلب OTP
curl -X POST http://localhost:8000/api/v1/auth/request-otp \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"phone": "0999123456"}'

# التحقق
curl -X POST http://localhost:8000/api/v1/auth/verify-otp \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"phone": "0999123456", "otp": "123456"}'
```
