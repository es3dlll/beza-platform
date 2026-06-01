# 15 - مواصفات API (API Specification)

```yaml
openapi: 3.0.0
info:
  title: Beza Merchant API — Products
  version: 1.0.0
servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development
paths:
  /merchant/products:
    get:
      summary: قائمة المنتجات
      security: [{ bearerAuth: [] }]
      parameters: [{ name: category, in: query, schema: { type: string } }]
      responses: { '200': { description: القائمة } }
    post:
      summary: إضافة منتج
      security: [{ bearerAuth: [] }]
      requestBody:
        content:
          multipart/form-data:
            schema:
              type: object
              required: [name, price_syp, price_usd]
              properties:
                name: { type: string }
                price_syp: { type: number }
                price_usd: { type: number }
                images: { type: array, items: { type: string, format: binary } }
      responses: { '201': { description: تم الإنشاء } }
  /merchant/products/{id}:
    get:
      summary: عرض منتج
      security: [{ bearerAuth: [] }]
    put:
      summary: تحديث منتج
      security: [{ bearerAuth: [] }]
    delete:
      summary: حذف منتج
      security: [{ bearerAuth: [] }]
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```
