# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI — Invest in Deal

```yaml
paths:
  /deals/{dealId}/invest:
    post:
      summary: المشاركة في صفقة
      operationId: investInDeal
      tags: [Deals]
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
              required: [amount]
              properties:
                amount:
                  type: number
                  minimum: 10
                  example: 1000
      responses:
        '201':
          description: تم الاستثمار بنجاح
        '422':
          description: خطأ في الطلب (رصيد، صفقة، إلخ)

  /deals:
    get:
      summary: قائمة الصفقات النشطة
      tags: [Deals]
      security: [bearerAuth: []]
      responses:
        '200':
          description: قائمة الصفقات
```

## cURL

```bash
# استثمار
curl -X POST http://localhost:8000/api/v1/deals/1/invest \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000}'

# قائمة الصفقات
curl http://localhost:8000/api/v1/deals \
  -H "Authorization: Bearer TOKEN"

# تفاصيل صفقة
curl http://localhost:8000/api/v1/deals/1 \
  -H "Authorization: Bearer TOKEN"
```
