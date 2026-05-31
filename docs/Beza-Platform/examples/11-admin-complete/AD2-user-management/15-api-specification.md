# 15 - مواصفات API كاملة (OpenAPI)

```yaml
openapi: 3.0.0
info:
  title: Beza Admin API — User Management
  version: 1.0.0

servers:
  - url: http://localhost:8000/api/v1

paths:
  /admin/users:
    get:
      summary: قائمة المستخدمين
      parameters:
        - name: search
          in: query
          schema: { type: string }
        - name: status
          in: query
          schema: { type: string, enum: [active, suspended, blocked, pending] }
        - name: kyc_status
          in: query
          schema: { type: string, enum: [not_submitted, pending, verified, rejected] }
        - name: role
          in: query
          schema: { type: string, enum: [all, merchant, agent, user] }
        - name: per_page
          in: query
          schema: { type: integer, default: 20 }
        - name: page
          in: query
          schema: { type: integer, default: 1 }
      responses:
        '200':
          description: قائمة المستخدمين
        '403':
          description: غير مصرح

  /admin/users/{id}:
    get:
      summary: تفاصيل مستخدم
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200':
          description: تفاصيل المستخدم مع المحافظ والمعاملات
        '404':
          description: المستخدم غير موجود

  /admin/users/{id}/suspend:
    put:
      summary: تعليق مستخدم
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
                reason:
                  type: string
      responses:
        '200':
          description: تم التعليق
        '422':
          description: لا يمكن تعليق مشرف

  /admin/users/{id}/activate:
    put:
      summary: تفعيل مستخدم
      responses:
        '200':
          description: تم التفعيل

  /admin/users/{id}/block:
    put:
      summary: حظر مستخدم
      responses:
        '200':
          description: تم الحظر

  /admin/users/{id}:
    delete:
      summary: حذف مستخدم (ناعم)
      responses:
        '200':
          description: تم الحذف
        '422':
          description: لا يمكن حذف الذات
```

## أمثلة cURL

```bash
# قائمة المستخدمين
curl http://localhost:8000/api/v1/admin/users?page=1&per_page=20 \
  -H "Authorization: Bearer admin_token"

# تفاصيل مستخدم
curl http://localhost:8000/api/v1/admin/users/42 \
  -H "Authorization: Bearer admin_token"

# تعليق مستخدم
curl -X PUT http://localhost:8000/api/v1/admin/users/42/suspend \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{"reason": "نشاط مشبوه"}'

# تفعيل مستخدم
curl -X PUT http://localhost:8000/api/v1/admin/users/42/activate \
  -H "Authorization: Bearer admin_token"

# حذف مستخدم
curl -X DELETE http://localhost:8000/api/v1/admin/users/42 \
  -H "Authorization: Bearer admin_token"
```
