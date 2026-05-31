# 15 - مواصفات API (API Specification)

## OpenAPI 3.0

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Auth
  version: 1.0.0

paths:
  /auth/logout:
    post:
      summary: تسجيل الخروج
      operationId: logout
      tags: [Auth]
      security:
        - bearerAuth: []
      responses:
        '200':
          description: تم تسجيل الخروج
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/LogoutSuccess'
        '401':
          description: غير مصادق

  /auth/logout-all:
    post:
      summary: تسجيل الخروج من كل الأجهزة
      operationId: logoutAll
      tags: [Auth]
      security:
        - bearerAuth: []
      responses:
        '200':
          description: تم تسجيل الخروج من كل الأجهزة

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    LogoutSuccess:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم تسجيل الخروج بنجاح" }

    LogoutAllSuccess:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم تسجيل الخروج من 3 أجهزة" }
        data:
          type: object
          properties:
            devices_count:
              type: integer
              example: 3
```

## cURL

```bash
# تسجيل خروج
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

# تسجيل خروج من كل الأجهزة
curl -X POST http://localhost:8000/api/v1/auth/logout-all \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```
