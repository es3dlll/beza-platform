# 15 - مواصفات API

```yaml
paths:
  /merchant/subscriptions:
    post:
      summary: إنشاء اشتراك
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [customer_phone, amount, currency, interval, max_cycles]
              properties:
                customer_phone: { type: string }
                amount: { type: number }
                currency: { type: string, enum: [SYP, USD] }
                interval: { type: string, enum: [monthly, yearly] }
                max_cycles: { type: integer }
      responses: { '201': { description: تم إنشاء الاشتراك } }
    get:
      summary: قائمة الاشتراكات
      responses: { '200': { description: القائمة } }
  /merchant/subscriptions/{id}/cancel:
    post:
      summary: إلغاء اشتراك
      responses: { '200': { description: تم الإلغاء } }
```

```bash
# إنشاء اشتراك
curl -X POST http://localhost:8000/api/v1/merchant/subscriptions \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"customer_phone": "963944123456", "amount": 100, "currency": "USD", "interval": "monthly", "max_cycles": 12}'
```
