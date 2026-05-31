# 15 - مواصفات API (API Specification)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Card Management API
  description: API لإدارة البطاقات (تحديد حد، حظر، إلغاء حظر، تقارير فقدان)
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
    Card:
      type: object
      properties:
        id: { type: integer }
        user_id: { type: integer }
        masked_pan: { type: string, example: "**** **** **** 1234" }
        status: { type: string, enum: [active, blocked, lost, expired] }
        daily_limit: { type: number }
        blocked_at: { type: string, format: date-time, nullable: true }
    UpdateLimitRequest:
      type: object
      required: [daily_limit]
      properties:
        daily_limit: { type: number, minimum: 1000, example: 100000 }
    ReportLostRequest:
      type: object
      properties:
        reason: { type: string }
security:
  - bearerAuth: []
paths:
  /cards/{id}/limit:
    put:
      summary: تحديث الحد اليومي للبطاقة
      description: تغيير الحد الأقصى للإنفاق اليومي
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/UpdateLimitRequest'
      responses:
        '200':
          description: تم تحديث الحد بنجاح
        '401': { description: غير مصدَّق }
        '403': { description: لا تملك صلاحية لهذه البطاقة }
        '422': { description: قيمة الحد غير صالحة }
  /cards/{id}/block:
    post:
      summary: حظر البطاقة
      description: حظر البطاقة بشكل مؤقت (تمنع أي عملية دفع)
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200': { description: تم حظر البطاقة }
        '400': { description: البطاقة بالفعل محظورة }
  /cards/{id}/unblock:
    post:
      summary: إلغاء حظر البطاقة
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200': { description: تم إلغاء الحظر }
        '400': { description: البطاقة غير محظورة }
  /cards/{id}/report-lost:
    post:
      summary: الإبلاغ عن فقدان البطاقة
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ReportLostRequest'
      responses:
        '200': { description: تم الإبلاغ عن الفقدان وسيتم إصدار بطاقة بديلة }
  /cards/{id}/transactions:
    get:
      summary: سجل معاملات البطاقة
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
        - name: page
          in: query
          schema: { type: integer, default: 1 }
        - name: per_page
          in: query
          schema: { type: integer, default: 15 }
      responses:
        '200':
          description: قائمة المعاملات
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: { type: array, items: { type: object } }
                  meta: { type: object }
```

## cURL
```bash
# حظر بطاقة
curl -X POST https://api.beza.sy/api/v1/cards/5/block \
  -H "Authorization: Bearer TOKEN"

# تحديث الحد اليومي
curl -X PUT https://api.beza.sy/api/v1/cards/5/limit \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"daily_limit":200000}'
```
