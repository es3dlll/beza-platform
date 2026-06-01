# 15 - مواصفات API كاملة — المصادقة الثنائية (2FA)

## OpenAPI 3.0

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Auth
  version: 1.0.0

paths:
  /auth/2fa/enable:
    post:
      summary: تفعيل المصادقة الثنائية
      operationId: enableTwoFactor
      tags: [2FA]
      security:
        - bearerAuth: []
      responses:
        '200':
          description: تم التفعيل — يرجى مسح QR
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/TwoFactorEnableResponse'
        '422':
          description: مفعل مسبقاً

  /auth/2fa/verify:
    post:
      summary: تأكيد تفعيل 2FA
      operationId: verifyTwoFactor
      tags: [2FA]
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/VerifyTwoFactorRequest'
      responses:
        '200':
          description: تم تأكيد التفعيل
        '422':
          description: رمز خاطئ

  /auth/2fa/disable:
    post:
      summary: تعطيل المصادقة الثنائية
      operationId: disableTwoFactor
      tags: [2FA]
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/DisableTwoFactorRequest'
      responses:
        '200':
          description: تم التعطيل

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    TwoFactorEnableResponse:
      type: object
      properties:
        success: { type: boolean, example: true }
        data:
          type: object
          properties:
            qr_code:
              type: string
              example: "data:image/png;base64,iVBOR..."
            secret:
              type: string
              example: "JBSWY3DPEHPK3PXP"

    VerifyTwoFactorRequest:
      type: object
      required: [code]
      properties:
        code:
          type: string
          minLength: 6
          maxLength: 6
          example: "123456"

    DisableTwoFactorRequest:
      type: object
      required: [password]
      properties:
        password:
          type: string
        code:
          type: string
          nullable: true
```

## cURL

```bash
# تفعيل 2FA
curl -X POST http://localhost:8000/api/v1/auth/2fa/enable \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

# تأكيد التفعيل
curl -X POST http://localhost:8000/api/v1/auth/2fa/verify \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"code": "123456"}'

# تعطيل 2FA
curl -X POST http://localhost:8000/api/v1/auth/2fa/disable \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"password": "mypassword", "code": "123456"}'
```
