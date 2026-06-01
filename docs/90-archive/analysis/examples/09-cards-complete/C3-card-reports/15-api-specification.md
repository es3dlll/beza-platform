# 15 - مواصفات API (API Specification)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Card Reports API
  description: API لتقارير وحالات البطاقات (ملخصات، تصدير، تحليلات)
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
    SpendingSummary:
      type: object
      properties:
        total_spent: { type: number, example: 250000 }
        transaction_count: { type: integer, example: 42 }
        period: { type: string, example: "2025-01" }
        average_per_day: { type: number, example: 8064 }
    MonthlyBreakdown:
      type: object
      properties:
        month: { type: string, example: "2025-01" }
        total: { type: number, example: 120000 }
        count: { type: integer, example: 15 }
    CategorySpending:
      type: object
      properties:
        category: { type: string, example: "طعام" }
        amount: { type: number, example: 50000 }
        percentage: { type: number, example: 20.0 }
security:
  - bearerAuth: []
paths:
  /cards/{id}/reports/summary:
    get:
      summary: ملخص إنفاق البطاقة
      description: عرض إجمالي الإنفاق وعدد المعاملات خلال فترة محددة
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
        - name: from
          in: query
          schema: { type: string, format: date }
        - name: to
          in: query
          schema: { type: string, format: date }
      responses:
        '200':
          description: ملخص الإنفاق
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SpendingSummary'
        '401': { description: غير مصدَّق }
        '403': { description: لا تملك صلاحية }
  /cards/{id}/reports/monthly:
    get:
      summary: توزيع الإنفاق الشهري
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200':
          description: قائمة شهرية
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: { type: array, items: { $ref: '#/components/schemas/MonthlyBreakdown' } }
  /cards/{id}/reports/by-category:
    get:
      summary: الإنفاق حسب الفئة
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200':
          description: توزيع حسب الفئة
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: { type: array, items: { $ref: '#/components/schemas/CategorySpending' } }
  /cards/{id}/reports/export:
    get:
      summary: تصدير تقرير
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
        - name: format
          in: query
          required: true
          schema: { type: string, enum: [csv, pdf, xlsx] }
      responses:
        '200':
          description: ملف التقرير
          content:
            text/csv: { schema: { type: string, format: binary } }
            application/pdf: { schema: { type: string, format: binary } }
```

## cURL
```bash
# ملخص إنفاق
curl -X GET "https://api.beza.sy/api/v1/cards/5/reports/summary?from=2025-01-01&to=2025-12-31" \
  -H "Authorization: Bearer TOKEN"

# تصدير CSV
curl -X GET "https://api.beza.sy/api/v1/cards/5/reports/export?format=csv" \
  -H "Authorization: Bearer TOKEN" \
  -o report.csv
```
