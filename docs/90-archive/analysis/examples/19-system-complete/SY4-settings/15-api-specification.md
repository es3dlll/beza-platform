# 15 - توثيق API: مواصفات OpenAPI الكاملة (API Specification)

## نظرة عامة (Overview)

توثيق OpenAPI 3.0 لجميع نقاط نهاية إدارة إعدادات النظام. جميع المسارات محمية بـ `auth:api` (JWT) وتتطلب صلاحية مسؤول.

```yaml
openapi: 3.0.3
info:
  title: SY4 - System Settings API
  description: |
    إدارة إعدادات النظام في منصة بيزا.
    جميع نقاط النهاية تتطلب توكن JWT مع صلاحية مسؤول.
  version: 1.0.0
  contact:
    name: Beza Platform Team
    email: dev@beza.sa

servers:
  - url: https://api.beza.sa/api/v1
    description: الإنتاج
  - url: https://staging-api.beza.sa/api/v1
    description: اختبار

components:
  securitySchemes:
    BearerJWT:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: أدخل توكن JWT الخاص بك مسبوقاً بـ Bearer

  schemas:
    ApiResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: تمت العملية بنجاح
        data:
          type: object
        errors:
          type: object

    SettingsGeneral:
      type: object
      properties:
        app_name:
          type: string
          example: Beza
        app_description:
          type: string
          example: منصة بيزا للمعاملات المالية
        timezone:
          type: string
          example: Asia/Riyadh
        locale:
          type: string
          enum: [ar, en]

    SettingsFeatures:
      type: object
      properties:
        gold:   { type: boolean, example: true }
        deals:  { type: boolean, example: true }
        cards:  { type: boolean, example: true }
        agents: { type: boolean, example: true }
        loans:  { type: boolean, example: false }

    SettingsFees:
      type: object
      properties:
        p2p:           { type: number, format: float, example: 0 }
        exchange:      { type: number, format: float, example: 1.5 }
        card_deposit:  { type: number, format: float, example: 2.5 }
        withdrawal:    { type: number, format: float, example: 1.0 }

    SettingsLimits:
      type: object
      properties:
        daily_transfer: { type: integer, example: 100000 }
        max_wallet:     { type: integer, example: 500000 }
        min_withdrawal: { type: integer, example: 100 }

    SettingsExchange:
      type: object
      properties:
        margin:          { type: number, format: float, example: 0.5 }
        update_interval: { type: integer, example: 300 }

    SettingsSecurity:
      type: object
      properties:
        max_attempts:    { type: integer, example: 5 }
        lockout_minutes: { type: integer, example: 30 }
        password_policy: { type: object }

    SettingsNotifications:
      type: object
      properties:
        default_channels:
          type: array
          items: { type: string }
          example: [push, email, sms]

    SettingsMail:
      type: object
      properties:
        smtp:
          type: object
          properties:
            host:     { type: string }
            port:     { type: integer, example: 587 }
            encryption: { type: string, enum: [tls, ssl, null] }
            username: { type: string }
            password: { type: string, format: password }
            from_address: { type: string, format: email }
            from_name: { type: string }

    SettingsMaintenance:
      type: object
      properties:
        mode:    { type: boolean, example: false }
        message: { type: string }
        allowed_ips:
          type: array
          items: { type: string, format: ipv4 }

    AllSettings:
      type: object
      properties:
        general:       { $ref: '#/components/schemas/SettingsGeneral' }
        features:      { $ref: '#/components/schemas/SettingsFeatures' }
        fees:          { $ref: '#/components/schemas/SettingsFees' }
        limits:        { $ref: '#/components/schemas/SettingsLimits' }
        exchange:      { $ref: '#/components/schemas/SettingsExchange' }
        security:      { $ref: '#/components/schemas/SettingsSecurity' }
        notifications: { $ref: '#/components/schemas/SettingsNotifications' }
        mail:          { $ref: '#/components/schemas/SettingsMail' }
        maintenance:   { $ref: '#/components/schemas/SettingsMaintenance' }

security:
  - BearerJWT: []

paths:
  /admin/system/settings:
    get:
      summary: عرض جميع إعدادات النظام
      tags: [System Settings]
      security:
        - BearerJWT: []
      responses:
        '200':
          description: تم جلب الإعدادات بنجاح
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean, example: true }
                  data: { $ref: '#/components/schemas/AllSettings' }
                  metadata:
                    type: object
                    properties:
                      total_groups: { type: integer, example: 9 }
                      total_settings: { type: integer, example: 23 }
                      cache_status: { type: string, enum: [warm, cold] }
        '401':
          description: غير مصرح (توكن مفقود أو غير صالح)
        '403':
          description: ليس لديك صلاحية مسؤول

  /admin/system/settings/{group}:
    get:
      summary: عرض إعدادات مجموعة محددة
      parameters:
        - name: group
          in: path
          required: true
          schema:
            type: string
            enum: [general, features, fees, limits, exchange, security, notifications, mail, maintenance]
      responses:
        '200':
          description: إعدادات المجموعة
        '404':
          description: المجموعة غير موجودة

    put:
      summary: تحديث إعدادات مجموعة محددة
      parameters:
        - name: group
          in: path
          required: true
          schema:
            type: string
            enum: [general, features, fees, limits, exchange, security, notifications, mail, maintenance]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              oneOf:
                - $ref: '#/components/schemas/SettingsGeneral'
                - $ref: '#/components/schemas/SettingsFeatures'
                - $ref: '#/components/schemas/SettingsFees'
                - $ref: '#/components/schemas/SettingsLimits'
                - $ref: '#/components/schemas/SettingsExchange'
                - $ref: '#/components/schemas/SettingsSecurity'
                - $ref: '#/components/schemas/SettingsNotifications'
                - $ref: '#/components/schemas/SettingsMail'
                - $ref: '#/components/schemas/SettingsMaintenance'
      responses:
        '200':
          description: تم تحديث الإعدادات بنجاح
        '422':
          description: بيانات غير صالحة
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ApiResponse'

  /admin/system/settings/mail/test:
    post:
      summary: اختبار اتصال SMTP
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                smtp: { $ref: '#/components/schemas/SettingsMail/properties/smtp' }
      responses:
        '200':
          description: تم الاتصال بنجاح
        '400':
          description: فشل الاتصال
```

## مثال: طلب GET كامل (Full Request Example)

```bash
// // طلب جميع الإعدادات
curl -X GET https://api.beza.sa/api/v1/admin/system/settings \
  -H "Authorization: Bearer <jwt-token>" \
  -H "Accept: application/json"

// // تحديث إعدادات الرسوم
curl -X PUT https://api.beza.sa/api/v1/admin/system/settings/fees \
  -H "Authorization: Bearer <jwt-token>" \
  -H "Content-Type: application/json" \
  -d '{
    "p2p": 1.0,
    "exchange": 2.0,
    "card_deposit": 2.5,
    "withdrawal": 1.5
  }'

// // اختبار SMTP
curl -X POST https://api.beza.sa/api/v1/admin/system/settings/mail/test \
  -H "Authorization: Bearer <jwt-token>" \
  -H "Content-Type: application/json" \
  -d '{
    "smtp": {
      "host": "smtp.gmail.com",
      "port": 587,
      "encryption": "tls",
      "username": "admin@beza.sa",
      "password": "secret"
    }
  }'
```

## رموز الحالة (Status Codes)

```php
// // 200 OK:     نجاح العملية (قراءة أو تحديث)
// // 400 Bad Request: مجموعة غير معروفة أو طلب خاطئ
// // 401 Unauthorized: توكن JWT مفقود أو منتهي الصلاحية
// // 403 Forbidden: المستخدم ليس لديه صلاحية مسؤول
// // 404 Not Found: المجموعة المطلوبة غير موجودة
// // 422 Unprocessable Entity: بيانات غير صالحة (خطأ تحقق)
// // 500 Internal Server Error: خطأ في الخادم
```
