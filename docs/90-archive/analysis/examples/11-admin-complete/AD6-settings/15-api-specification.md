# 15 - مواصفات API كاملة (OpenAPI)

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Admin Settings
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات إعدادات النظام — لوحة المشرف
    جميع الاستجابات باللغة العربية

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development

paths:
  /admin/settings:
    get:
      summary: عرض جميع الإعدادات
      security:
        - bearerAuth: []
        - adminAuth: []
      responses:
        '200':
          description: جميع الإعدادات
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean }
                  data:
                    type: object
                    properties:
                      general:
                        properties:
                          maintenance_mode: { type: boolean }
                          kyc_required: { type: boolean }
                      fees:
                        type: object
                        properties:
                          transfer: { type: number }
                          exchange: { type: number }
                          card_load: { type: number }
                          merchant:
                            type: object
                            properties:
                              percent: { type: number }
                              fixed: { type: number }
                      limits:
                        type: object
                        properties:
                          daily_transfer_usd: { type: number }
                          daily_transfer_syp: { type: number }
                          min_deposit_usd: { type: number }
                          min_deposit_syp: { type: number }
                      exchange:
                        type: object
                        properties:
                          rate: { type: number }
                          margin: { type: number }
    put:
      summary: تحديث الإعدادات العامة
      security:
        - bearerAuth: []
        - adminAuth: []
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                maintenance_mode: { type: boolean }
                kyc_required: { type: boolean }

  /admin/settings/fees:
    put:
      summary: تحديث رسوم المعاملات
      security:
        - bearerAuth: []
        - adminAuth: []

  /admin/settings/limits:
    put:
      summary: تحديث الحدود
      security:
        - bearerAuth: []
        - adminAuth: []

  /admin/settings/exchange-rate:
    put:
      summary: تحديث سعر الصرف
      security:
        - bearerAuth: []
        - adminAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [rate, margin]
              properties:
                rate: { type: number, minimum: 1 }
                margin: { type: number, minimum: 0, maximum: 100 }

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
    adminAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

## أمثلة cURL

```bash
# عرض الإعدادات
curl http://localhost:8000/api/v1/admin/settings \
  -H "Authorization: Bearer admin_token"

# تحديث رسوم التحويل
curl -X PUT http://localhost:8000/api/v1/admin/settings/fees \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{"transfer": 1.5, "exchange": 0.75}'

# تحديث سعر الصرف
curl -X PUT http://localhost:8000/api/v1/admin/settings/exchange-rate \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{"rate": 13500, "margin": 0.5}'

# تفعيل وضع الصيانة
curl -X PUT http://localhost:8000/api/v1/admin/settings \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{"maintenance_mode": true}'
```
