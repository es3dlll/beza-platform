# 15 - مواصفات API كاملة (OpenAPI 3.0)

```yaml
openapi: 3.0.0
info:
  title: Beza Payment API — Notifications
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات نظام الإشعارات
    جميع الاستجابات باللغة العربية

servers:
  - url: http://localhost:8000/api/v1
    description: Localhost Development

paths:
  /notifications:
    get:
      summary: قائمة الإشعارات
      operationId: listNotifications
      tags: [Notifications]
      security: [{ bearerAuth: [] }]
      parameters:
        - name: per_page
          in: query
          schema: { type: integer, default: 20 }
        - name: page
          in: query
          schema: { type: integer, default: 1 }
      responses:
        '200':
          description: قائمة الإشعارات
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                    example: true
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/NotificationResource'
                  meta:
                    type: object
                    properties:
                      total: { type: integer, example: 45 }
                      unread_count: { type: integer, example: 3 }
                      current_page: { type: integer, example: 1 }
                      per_page: { type: integer, example: 20 }

  /notifications/{id}/read:
    post:
      summary: تحديد إشعار كمقروء
      operationId: markAsRead
      tags: [Notifications]
      security: [{ bearerAuth: [] }]
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200':
          description: تم التحديد
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean, example: true }
                  message: { type: string, example: "تم تحديد الإشعار كمقروء" }

  /notifications/read-all:
    post:
      summary: تحديد الكل كمقروء
      operationId: markAllAsRead
      tags: [Notifications]
      security: [{ bearerAuth: [] }]
      responses:
        '200':
          description: تم التحديد

  /notifications/stats:
    get:
      summary: إحصائيات الإشعارات
      operationId: notificationStats
      tags: [Notifications]
      security: [{ bearerAuth: [] }]
      responses:
        '200':
          description: الإحصائيات

  /admin/notifications/send:
    post:
      summary: إرسال إشعار (مشرف)
      operationId: sendNotification
      tags: [Admin]
      security:
        - bearerAuth: []
        - adminAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [user_id, type, data]
              properties:
                user_id: { type: integer }
                type:
                  type: string
                  enum: [transfer_in, transfer_out, deposit, withdrawal, kyc_update, deal_update]
                data:
                  type: object
                channels:
                  type: array
                  items:
                    type: string
                    enum: [fcm, database, sms]
      responses:
        '201':
          description: تم إرسال الإشعار

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

  schemas:
    NotificationResource:
      type: object
      properties:
        id: { type: integer, example: 1 }
        type: { type: string, example: "transfer_in" }
        title: { type: string, example: "لقد استلمت 50 USD من أحمد" }
        body: { type: string, example: "تم إيداع 50 دولار في محفظتك" }
        data:
          type: object
          properties:
            amount: { type: number, example: 50 }
            currency: { type: string, example: "USD" }
            from_name: { type: string, example: "أحمد" }
            transaction_id: { type: integer, example: 123 }
        status: { type: string, enum: [sent, delivered, read], example: "sent" }
        sent_at: { type: string, format: date-time }
        read_at: { type: string, format: date-time, nullable: true }
        created_at: { type: string, format: date-time }
```

## cURL

```bash
# قائمة الإشعارات
curl http://localhost:8000/api/v1/notifications \
  -H "Authorization: Bearer {token}"

# تحديد كمقروء
curl -X POST http://localhost:8000/api/v1/notifications/1/read \
  -H "Authorization: Bearer {token}"

# تحديد الكل كمقروء
curl -X POST http://localhost:8000/api/v1/notifications/read-all \
  -H "Authorization: Bearer {token}"

# إحصائيات
curl http://localhost:8000/api/v1/notifications/stats \
  -H "Authorization: Bearer {token}"

# إرسال إشعار (مشرف)
curl -X POST http://localhost:8000/api/v1/admin/notifications/send \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "type": "transfer_in", "data": {"amount": 100, "currency": "USD", "from_name": "محمد"}, "channels": ["fcm", "database"]}'
```
