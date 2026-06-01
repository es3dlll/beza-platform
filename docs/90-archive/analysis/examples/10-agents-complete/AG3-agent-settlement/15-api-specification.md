# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Agent Settlement API
  description: API لتسوية أرباح الوكلاء (طلب، موافقة، إلغاء)
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
    SettlementRequest:
      type: object
      required: [amount, bank_account_id]
      properties:
        amount: { type: number, minimum: 10000, example: 100000 }
        bank_account_id: { type: integer, example: 1 }
        notes: { type: string }
    Settlement:
      type: object
      properties:
        id: { type: integer }
        agent_id: { type: integer }
        amount: { type: number }
        fee: { type: number }
        net_amount: { type: number }
        status: { type: string, enum: [pending, approved, paid, cancelled, rejected] }
        bank_account_id: { type: integer }
        approved_at: { type: string, format: date-time, nullable: true }
        paid_at: { type: string, format: date-time, nullable: true }
        created_at: { type: string, format: date-time }
    ApproveRequest:
      type: object
      properties:
        fee: { type: number, example: 2500 }
        notes: { type: string }
security:
  - bearerAuth: []
paths:
  /agent/settlements:
    post:
      summary: طلب تسوية جديدة
      description: تقديم طلب لسحب أرباح الوكيل إلى حسابه البنكي
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/SettlementRequest'
      responses:
        '201':
          description: تم تقديم الطلب
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean, example: true }
                  data: { $ref: '#/components/schemas/Settlement' }
        '400': { description: المبلغ يتجاوز الرصيد المتاح }
        '401': { description: غير مصدَّق }
        '422': { description: المبلغ أقل من الحد الأدنى }
    get:
      summary: سجل طلبات التسوية
      parameters:
        - name: status
          in: query
          schema: { type: string, enum: [pending, approved, paid, cancelled, rejected] }
      responses:
        '200':
          description: قائمة الطلبات
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: { type: array, items: { $ref: '#/components/schemas/Settlement' } }
  /agent/settlements/{id}/cancel:
    post:
      summary: إلغاء طلب تسوية
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200': { description: تم الإلغاء }
        '400': { description: لا يمكن إلغاء طلب تمت الموافقة عليه }
  /admin/agent-settlements/{id}/approve:
    post:
      summary: الموافقة على طلب تسوية (للمشرف)
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ApproveRequest'
      responses:
        '200': { description: تمت الموافقة }
        '404': { description: الطلب غير موجود }
  /admin/agent-settlements/{id}/reject:
    post:
      summary: رفض طلب تسوية (للمشرف)
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                reason: { type: string }
      responses:
        '200': { description: تم الرفض }
```

## cURL
```bash
# طلب تسوية
curl -X POST https://api.beza.sy/api/v1/agent/settlements \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"amount":100000,"bank_account_id":1}'

# الموافقة على تسوية (مشرف)
curl -X POST https://api.beza.sy/api/v1/admin/agent-settlements/5/approve \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"fee":2500,"notes":"موافقة"}'
```
