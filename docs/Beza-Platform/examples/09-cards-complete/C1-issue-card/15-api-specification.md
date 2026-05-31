# 15 - مواصفات API (API Specification)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Cards API
  description: API لإدارة بطاقات الدفع (إصدار، إدارة، تقارير، محافظ رقمية)
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
        type: { type: string, enum: [virtual, physical] }
        currency: { type: string, enum: [SYP, USD] }
        masked_pan: { type: string, example: "**** **** **** 1234" }
        expiry_date: { type: string, example: "12/28" }
        status: { type: string, enum: [active, blocked, lost, expired] }
        daily_limit: { type: number }
        created_at: { type: string, format: date-time }
    IssueCardRequest:
      type: object
      required: [type, currency]
      properties:
        type: { type: string, enum: [virtual, physical] }
        currency: { type: string, enum: [SYP, USD] }
        daily_limit: { type: number, example: 50000 }
        shipping_address: { type: string }
security:
  - bearerAuth: []
paths:
  /cards/issue:
    post:
      summary: إصدار بطاقة جديدة
      description: إصدار بطاقة افتراضية أو فيزيائية مرتبطة بمحفظة المستخدم
      operationId: issueCard
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/IssueCardRequest'
      responses:
        '201':
          description: تم إصدار البطاقة بنجاح
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean, example: true }
                  data: { $ref: '#/components/schemas/Card' }
        '400':
          description: رصيد غير كافٍ أو حد البطاقة اليومي غير صحيح
        '401':
          description: غير مصدَّق (Unauthorized)
        '409':
          description: بطاقة افتراضية موجودة مسبقاً
        '422':
          description: بيانات الإدخال غير صالحة (Validation Error)
  /cards:
    get:
      summary: قائمة البطاقات
      description: عرض جميع بطاقات المستخدم مع إخفاء PAN
      operationId: listCards
      responses:
        '200':
          description: قائمة البطاقات
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Card'
  /cards/{id}:
    get:
      summary: تفاصيل بطاقة محددة
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200': { description: تفاصيل البطاقة }
        '403': { description: لا تملك صلاحية الوصول لهذه البطاقة }
        '404': { description: البطاقة غير موجودة }
```

## cURL
```bash
# إصدار بطاقة افتراضية
curl -X POST https://api.beza.sy/api/v1/cards/issue \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type":"virtual","currency":"SYP","daily_limit":50000}'

# قائمة البطاقات
curl -X GET https://api.beza.sy/api/v1/cards \
  -H "Authorization: Bearer TOKEN"
```
