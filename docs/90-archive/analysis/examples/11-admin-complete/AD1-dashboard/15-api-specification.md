# 15 - مواصفات API كاملة (OpenAPI)

## OpenAPI 3.0 Specification

```yaml
openapi: 3.0.0
info:
  title: Beza Admin API — Dashboard
  version: 1.0.0
  description: لوحة تحكم المشرف - الإحصائيات

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost

paths:
  /admin/dashboard/stats:
    get:
      summary: إحصائيات لوحة التحكم
      description: |
        جميع مؤشرات الأداء الرئيسية للمنصة
        - البيانات محسّمة لمدة 5 دقائق
        - تحديث تلقائي كل 30 ثانية من الواجهة
      operationId: getDashboardStats
      tags:
        - Admin Dashboard
      security:
        - bearerAuth: []
        - adminAuth: []
      parameters:
        - name: period
          in: query
          schema:
            type: string
            enum: [7d, 30d, 90d, 1y]
            default: 30d
          description: فترة المخططات البيانية
      responses:
        '200':
          description: نجاح — بيانات الإحصائيات
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/DashboardResponse'
        '403':
          description: صلاحيات المشرف مطلوبة
        '401':
          description: غير مصادق

  /admin/dashboard/refresh:
    post:
      summary: تحديث إجباري للبيانات
      description: مسح Cache وإعادة توليد البيانات
      operationId: refreshDashboard
      tags:
        - Admin Dashboard
      security:
        - bearerAuth: []
        - adminAuth: []
      responses:
        '200':
          description: تم التحديث بنجاح

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
    adminAuth:
      type: apiKey
      in: header
      name: X-Admin-Key

  schemas:
    DashboardSummary:
      type: object
      properties:
        total_users:
          type: integer
          example: 15420
        active_users:
          type: integer
          example: 8910
        total_transactions:
          type: integer
          example: 284500
        transaction_volume:
          type: number
          example: 12500000.00
        total_wallets_balance:
          type: number
          example: 8750000.00
        merchants_count:
          type: integer
          example: 342
        agents_count:
          type: integer
          example: 89
        total_fees:
          type: number
          example: 452000.00

    ChartDataPoint:
      type: object
      properties:
        date:
          type: string
          format: date
          example: "2026-04-27"
        value:
          type: number
          example: 12500

    DashboardCharts:
      type: object
      properties:
        revenue:
          type: array
          items:
            $ref: '#/components/schemas/ChartDataPoint'
        volume:
          type: array
          items:
            $ref: '#/components/schemas/ChartDataPoint'
        user_growth:
          type: array
          items:
            $ref: '#/components/schemas/ChartDataPoint'
        daily_active:
          type: array
          items:
            $ref: '#/components/schemas/ChartDataPoint'

    TopMerchant:
      type: object
      properties:
        id: { type: integer, example: 1 }
        name: { type: string, example: "متجر الإلكترونيات" }
        volume: { type: number, example: 850000 }
        transactions: { type: integer, example: 1200 }

    DashboardResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        data:
          type: object
          properties:
            summary:
              $ref: '#/components/schemas/DashboardSummary'
            charts:
              $ref: '#/components/schemas/DashboardCharts'
            top_merchants:
              type: array
              items:
                $ref: '#/components/schemas/TopMerchant'
        meta:
          type: object
          properties:
            cached_at:
              type: string
              format: date-time
            expires_in:
              type: integer
              example: 240
```

## أمثلة cURL

```bash
# الحصول على الإحصائيات
curl -X GET http://localhost:8000/api/v1/admin/dashboard/stats \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|admin_token"

# تحديث إجباري
curl -X POST http://localhost:8000/api/v1/admin/dashboard/refresh \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|admin_token"
```
