# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Agent Registration API
  description: API لتسجيل الوكلاء الجدد والتحقق من هوياتهم من قبل الإدارة
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
    RegisterAgentRequest:
      type: object
      required: [full_name, phone, id_number]
      properties:
        full_name: { type: string, example: "أحمد خالد" }
        phone: { type: string, example: "963944123456" }
        id_number: { type: string, example: "ID123456" }
        id_photo: { type: string, format: byte, description: "صورة الهوية بصيغة Base64" }
        location_lat: { type: number, example: 33.5138 }
        location_lng: { type: number, example: 36.2765 }
        address: { type: string, example: "دمشق, سوريا" }
    AgentResponse:
      type: object
      properties:
        id: { type: integer }
        user_id: { type: integer }
        full_name: { type: string }
        phone: { type: string }
        status: { type: string, enum: [pending, approved, rejected] }
        rating: { type: number }
        available: { type: boolean }
        created_at: { type: string, format: date-time }
security:
  - bearerAuth: []
paths:
  /agents/register:
    post:
      summary: تسجيل وكيل جديد
      description: تقديم طلب التسجيل كوكيل في منصة Beza
      operationId: registerAgent
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/RegisterAgentRequest'
      responses:
        '201':
          description: تم تقديم الطلب بنجاح (بحاجة مراجعة)
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean, example: true }
                  data: { $ref: '#/components/schemas/AgentResponse' }
        '401': { description: غير مصدَّق }
        '409': { description: المستخدم مسجل كوكيل مسبقاً }
        '422': { description: بيانات غير صالحة (مثل رقم هاتف مكرر) }
  /admin/agents:
    get:
      summary: قائمة الوكلاء (للمشرف)
      description: عرض جميع طلبات التسجيل مع إمكانية التصفية حسب الحالة
      security:
        - bearerAuth: []
      parameters:
        - name: status
          in: query
          schema: { type: string, enum: [pending, approved, rejected] }
        - name: page
          in: query
          schema: { type: integer, default: 1 }
      responses:
        '200':
          description: قائمة الوكلاء
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: { type: array, items: { $ref: '#/components/schemas/AgentResponse' } }
                  meta: { type: object }
  /admin/agents/{id}/verify:
    post:
      summary: التحقق من هوية الوكيل (للمشرف)
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
              type: object
              required: [status]
              properties:
                status: { type: string, enum: [approved, rejected] }
                rejection_reason: { type: string }
      responses:
        '200': { description: تم تحديث حالة الوكيل }
        '404': { description: الوكيل غير موجود }
```

## cURL
```bash
# تسجيل وكيل جديد
curl -X POST https://api.beza.sy/api/v1/agents/register \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"full_name":"أحمد خالد","phone":"963944123456","id_number":"ID123456"}'

# الموافقة على وكيل (مشرف)
curl -X POST https://api.beza.sy/api/v1/admin/agents/5/verify \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"approved"}'
```
