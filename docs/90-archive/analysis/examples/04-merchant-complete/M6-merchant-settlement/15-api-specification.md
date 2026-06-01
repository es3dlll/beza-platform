# 15 - مواصفات API

```yaml
paths:
  /merchant/settlement:
    post:
      summary: طلب تسوية
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [currency]
              properties:
                currency: { type: string, enum: [SYP, USD] }
      responses: { '201': { description: تم تقديم الطلب } }
  /merchant/settlement/calculate:
    post:
      summary: حساب التسوية المتوقعة
      responses: { '200': { description: تفاصيل الحساب } }
  /merchant/settlement/history:
    get:
      summary: تاريخ التسويات
      responses: { '200': { description: القائمة } }
```

```bash
# طلب تسوية
curl -X POST http://localhost:8000/api/v1/merchant/settlement \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"currency": "USD"}'

# حساب التسوية
curl -X POST http://localhost:8000/api/v1/merchant/settlement/calculate \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"currency": "USD"}'
```
