# 15 - مواصفات API

```yaml
paths:
  /merchant/orders:
    get:
      summary: قائمة الطلبات
      parameters:
        - { name: status, in: query, schema: { type: string, enum: [pending,processing,shipped,delivered,cancelled] } }
        - { name: date_from, in: query, schema: { type: string, format: date } }
      responses: { '200': { description: قائمة الطلبات } }
  /merchant/orders/{id}/status:
    patch:
      summary: تحديث حالة الطلب
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [status]
              properties:
                status: { type: string, enum: [pending,processing,shipped,delivered,cancelled] }
                notes: { type: string }
      responses: { '200': { description: تم التحديث } }
```

```bash
curl -X PATCH http://localhost:8000/api/v1/merchant/orders/1/status \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "processing"}'
```
