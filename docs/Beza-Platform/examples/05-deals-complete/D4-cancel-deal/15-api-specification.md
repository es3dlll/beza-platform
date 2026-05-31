# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI — Cancel Deal

```yaml
paths:
  /admin/deals/{dealId}/cancel:
    post:
      summary: إلغاء صفقة واسترجاع المبالغ (Admin)
      operationId: cancelDeal
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
              required: [reason]
              properties:
                reason:
                  type: string
                  minLength: 10
                  example: "تعذر توفير الشحنة بسبب مشاكل لوجستية"
      responses:
        '200':
          description: تم الإلغاء والاسترجاع بنجاح
        '422':
          description: خطأ (صفقة غير قابلة للإلغاء)
```

## cURL

```bash
curl -X POST http://localhost:8000/api/v1/admin/deals/1/cancel \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"reason": "تعذر توفير الشحنة بسبب مشاكل لوجستية"}'
```
