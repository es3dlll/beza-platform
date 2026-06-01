# 15 - مواصفات API كاملة (OpenAPI)

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Admin Reports
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات التقارير — لوحة المشرف
    جميع الاستجابات باللغة العربية

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development

paths:
  /admin/reports/daily:
    get:
      summary: التقرير اليومي
      security:
        - bearerAuth: []
        - adminAuth: []
      parameters:
        - name: date
          in: query
          schema: { type: string, format: date }
          description: التاريخ (YYYY-MM-DD), افتراضي اليوم
      responses:
        '200':
          description: التقرير اليومي
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean }
                  data:
                    type: object
                    properties:
                      date: { type: string }
                      total_transactions: { type: integer }
                      total_volume: { type: number }
                      total_fees: { type: number }
                      new_users: { type: integer }
                      active_users: { type: integer }
                      avg_transaction: { type: number }
                      transaction_breakdown: { type: object }
                      growth_percent: { type: number, nullable: true }

  /admin/reports/monthly:
    get:
      summary: التقرير الشهري
      security:
        - bearerAuth: []
        - adminAuth: []
      parameters:
        - name: year
          in: query
          schema: { type: integer }
        - name: month
          in: query
          schema: { type: integer, min: 1, max: 12 }

  /admin/reports/financial:
    get:
      summary: التقرير المالي
      security:
        - bearerAuth: []
        - adminAuth: []
      parameters:
        - name: from
          in: query
          schema: { type: string, format: date }
        - name: to
          in: query
          schema: { type: string, format: date }

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
# التقرير اليومي
curl "http://localhost:8000/api/v1/admin/reports/daily?date=2026-05-27" \
  -H "Authorization: Bearer admin_token"

# التقرير الشهري
curl "http://localhost:8000/api/v1/admin/reports/monthly?year=2026&month=5" \
  -H "Authorization: Bearer admin_token"

# التقرير المالي
curl "http://localhost:8000/api/v1/admin/reports/financial?from=2026-01-01&to=2026-05-27" \
  -H "Authorization: Bearer admin_token"
```
