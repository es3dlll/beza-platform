# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI — Complete Deal

```yaml
paths:
  /admin/deals/{dealId}/complete:
    post:
      summary: إتمام صفقة وتوزيع أرباح (Admin)
      operationId: completeDeal
      tags: [Admin Deals]
      security: [bearerAuth: []]
      parameters:
        - name: dealId
          in: path
          required: true
          schema: { type: integer }
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [profit_actual]
              properties:
                profit_actual:
                  type: number
                  description: نسبة الربح الفعلية (مئوية)
                  example: 18.50
      responses:
        '200':
          description: تم التوزيع بنجاح
        '422':
          description: خطأ (صفقة غير قابلة للإكمال)
```

## cURL

```bash
curl -X POST http://localhost:8000/api/v1/admin/deals/1/complete \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"profit_actual": 18.5}'
```
