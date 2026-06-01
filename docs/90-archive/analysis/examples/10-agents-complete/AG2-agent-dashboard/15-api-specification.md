# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Agent Dashboard API
  description: API للوحة تحكم الوكيل (إحصائيات، نشاطات، عمولات)
  version: 1.0.0
servers:
  - url: https://api.beza.sy/api/v1
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
  schemas:
    DashboardStats:
      type: object
      properties:
        total_transactions: { type: integer, example: 156 }
        total_volume: { type: number, example: 12500000 }
        commission_earned: { type: number, example: 125000 }
        today_count: { type: integer, example: 12 }
        today_volume: { type: number, example: 980000 }
        rating: { type: number, example: 4.8 }
    ActivityItem:
      type: object
      properties:
        id: { type: integer }
        type: { type: string, enum: [deposit, withdrawal, transfer, payment] }
        amount: { type: number }
        customer_name: { type: string }
        commission: { type: number }
        created_at: { type: string, format: date-time }
    CommissionSummary:
      type: object
      properties:
        total_earned: { type: number, example: 500000 }
        pending: { type: number, example: 75000 }
        withdrawn: { type: number, example: 425000 }
security:
  - bearerAuth: []
paths:
  /agent/dashboard:
    get:
      summary: إحصائيات لوحة التحكم
      description: عرض إجمالي المعاملات، حجم التداول، العمولات، وإحصائيات اليوم
      responses:
        '200':
          description: إحصائيات الوكيل
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/DashboardStats'
        '401': { description: غير مصدَّق }
        '403': { description: ليس لديك صلاحية وكيل }
  /agent/dashboard/activities:
    get:
      summary: آخر النشاطات
      parameters:
        - name: limit
          in: query
          schema: { type: integer, default: 20 }
      responses:
        '200':
          description: قائمة بآخر النشاطات
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: { type: array, items: { $ref: '#/components/schemas/ActivityItem' } }
  /agent/dashboard/chart/daily:
    get:
      summary: رسم بياني للأداء اليومي
      responses:
        '200':
          description: بيانات المخطط
          content:
            application/json:
              schema:
                type: object
                properties:
                  labels: { type: array, items: { type: string }, example: ["السبت", "الأحد", "الاثنين"] }
                  values: { type: array, items: { type: number }, example: [15000, 22000, 18000] }
  /agent/profile:
    get:
      summary: الملف الشخصي للوكيل
      responses:
        '200':
          description: بيانات الوكيل
  /agent/availability:
    put:
      summary: تحديث حالة التوفر
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [available]
              properties:
                available: { type: boolean }
      responses:
        '200': { description: تم تحديث الحالة }
  /agent/commissions:
    get:
      summary: سجل العمولات
      responses:
        '200':
          description: العمولات
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CommissionSummary'
```

## cURL
```bash
# لوحة التحكم
curl -X GET https://api.beza.sy/api/v1/agent/dashboard \
  -H "Authorization: Bearer TOKEN"

# تحديث التوفر
curl -X PUT https://api.beza.sy/api/v1/agent/availability \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"available":false}'
```
