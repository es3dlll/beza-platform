# 15 - مواصفات API: OpenAPI لجميع نقاط الإدارة (OpenAPI Specification for All Management Endpoints)

<div dir="rtl">

## مواصفات OpenAPI كاملة

```yaml
openapi: 3.0.3
info:
  title: Beza Platform - System Management API (SY3-manage)
  description: |
    واجهة إدارة النظام لمنصة بزة. توفر أدوات لإدارة الكاش، السجلات،
    قائمة الانتظار، الصيانة، النسخ الاحتياطي، ومعلومات النظام.
    جميع النقاط تتطلب مصادقة JWT (auth:api) ودور admin.
  version: 1.0.0
  contact:
    name: Beza Platform Team
    url: https://beza-platform.example.com

servers:
  - url: https://api.beza-platform.example.com/api/v1
    description: خادم الإنتاج
  - url: https://staging-api.beza-platform.example.com/api/v1
    description: خادم الاختبار

components:
  securitySchemes:
    BearerJWT:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: أدخل رمز JWT الخاص بك (مصادقة auth:api)

  schemas:
    # الهياكل الأساسية
    SuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: تمت العملية بنجاح

    ErrorResponse:
      type: object
      properties:
        success:
          type: boolean
          example: false
        message:
          type: string
          example: حدث خطأ أثناء تنفيذ العملية
        errors:
          type: object
          description: تفاصيل الأخطاء (للتحقق من الصحة)

    # هياكل محددة
    CacheClearResult:
      type: object
      properties:
        application:
          $ref: '#/components/schemas/CommandResult'
        config:
          $ref: '#/components/schemas/CommandResult'
        route:
          $ref: '#/components/schemas/CommandResult'
        view:
          $ref: '#/components/schemas/CommandResult'

    CommandResult:
      type: object
      properties:
        success:
          type: boolean
          example: true
        command:
          type: string
          example: cache:clear
        output:
          type: string
          example: Application cache cleared!

    BackupResource:
      type: object
      properties:
        filename:
          type: string
          example: backup_2026-05-27_14-30-00.sql.gz
        size:
          type: integer
          example: 15728640
        size_formatted:
          type: string
          example: 15 MB
        created_at:
          type: string
          format: date-time
          example: 2026-05-27 14:30:00

    QueueStatus:
      type: object
      properties:
        driver:
          type: string
          example: database
        connection:
          type: object
        pending:
          type: integer
          example: 5
        failed:
          type: integer
          example: 0
        workers_info:
          type: string
          example: Processing jobs...

    SystemInfo:
      type: object
      properties:
        php:
          type: object
          properties:
            version:
              type: string
              example: 8.2.12
            memory_limit:
              type: string
              example: 256M
        laravel:
          type: object
          properties:
            version:
              type: string
              example: 11.0.0
            environment:
              type: string
              example: production

    LogFile:
      type: object
      properties:
        name:
          type: string
          example: laravel.log
        size:
          type: integer
          example: 1048576
        size_formatted:
          type: string
          example: 1 MB
        modified:
          type: string
          format: date-time

    ScheduledTask:
      type: object
      properties:
        command:
          type: string
          example: app:send-reports
        expression:
          type: string
          example: 0 0 * * *
        readable:
          type: string
          example: كل يوم في منتصف الليل
        timezone:
          type: string
          example: UTC

  parameters:
    BackupId:
      name: id
      in: path
      required: true
      schema:
        type: string
        pattern: '^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$'
      description: معرف النسخة الاحتياطية (اسم الملف)
      example: backup_2026-05-27_14-30-00.sql.gz

    LogFileName:
      name: file
      in: path
      required: true
      schema:
        type: string
        pattern: '^[a-zA-Z0-9_\-]+\.log$'
      description: اسم ملف السجل
      example: laravel.log

security:
  - BearerJWT: []

paths:
  # ========== الكاش (Cache) ==========
  /admin/system/cache/clear:
    post:
      tags: [Cache]
      summary: مسح جميع أنواع الذاكرة المؤقتة
      description: مسح كاش التطبيق والإعدادات والمسارات والقوالب
      operationId: cacheClear
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: تم مسح الكاش بنجاح
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                    example: true
                  message:
                    type: string
                    example: تم مسح جميع أنواع الكاش بنجاح
                  data:
                    $ref: '#/components/schemas/CacheClearResult'
        '401':
          description: غير مصرح به (JWT مفقود أو غير صالح)
        '403':
          description: ممنوع (ليس لديك صلاحية admin)

  /admin/system/cache/optimize:
    post:
      tags: [Cache]
      summary: تحسين الكاش وتخزين الإعدادات والمسارات
      description: يقوم بتخزين الإعدادات والمسارات في ملفات لتحسين الأداء
      operationId: cacheOptimize
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: تم تحسين الكاش بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SuccessResponse'

  # ========== السجلات (Logs) ==========
  /admin/system/log/view:
    post:
      tags: [Logs]
      summary: عرض أحدث إدخالات السجل
      description: يعرض آخر 100 سطر من سجل Laravel
      operationId: logView
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: محتوى السجل
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: object
                    properties:
                      content:
                        type: string
                        description: آخر 100 سطر من السجل
                      lines:
                        type: integer
                      file:
                        type: string

  /admin/system/log/clear:
    post:
      tags: [Logs]
      summary: مسح ملفات السجل
      description: يقوم بحذف جميع ملفات .log في مجلد السجلات
      operationId: logClear
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: تم مسح ملفات السجل

  /admin/system/logs:
    get:
      tags: [Logs]
      summary: عرض ملفات السجل مع الأحجام
      description: يعرض قائمة بجميع ملفات السجل وأحجامها
      operationId: logsList
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: قائمة ملفات السجل
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/LogFile'

  /admin/system/logs/{file}:
    get:
      tags: [Logs]
      summary: عرض ملف سجل محدد
      description: يعرض محتوى ملف سجل معين
      operationId: logsShow
      security:
        - BearerJWT: []
        - admin: []
      parameters:
        - $ref: '#/components/parameters/LogFileName'
      responses:
        '200':
          description: محتوى ملف السجل
        '422':
          description: اسم الملف غير صالح

  # ========== قائمة الانتظار (Queue) ==========
  /admin/system/queue/status:
    get:
      tags: [Queue]
      summary: حالة عمال قائمة الانتظار
      description: يعرض حالة قائمة الانتظار وعدد المهام المعلقة والفاشلة
      operationId: queueStatus
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: حالة قائمة الانتظار
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    $ref: '#/components/schemas/QueueStatus'

  /admin/system/queue/restart:
    post:
      tags: [Queue]
      summary: إعادة تشغيل عمال قائمة الانتظار
      description: يرسل إشارة لجميع العمال لإعادة التشغيل
      operationId: queueRestart
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: تم إعادة تشغيل العمال

  /admin/system/schedule:
    get:
      tags: [Queue]
      summary: عرض المهام المجدولة
      description: يعرض جميع المهام المسجلة في جدولة المهام
      operationId: scheduleList
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: قائمة المهام المجدولة
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/ScheduledTask'

  # ========== الصيانة (Maintenance) ==========
  /admin/system/maintenance:
    post:
      tags: [Maintenance]
      summary: تبديل وضع الصيانة
      description: تفعيل أو تعطيل وضع الصيانة للتطبيق
      operationId: maintenanceToggle
      security:
        - BearerJWT: []
        - admin: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required:
                - enabled
              properties:
                enabled:
                  type: boolean
                  description: true للتفعيل، false للتعطيل
                  example: true
                message:
                  type: string
                  description: رسالة الصيانة (اختياري)
                  example: نحن تحت الصيانة حالياً. سنعود قريباً.
                retry:
                  type: integer
                  description: دقائق إعادة المحاولة (اختياري)
                  minimum: 1
                  maximum: 1440
                  example: 60
      responses:
        '200':
          description: تم تبديل وضع الصيانة
        '422':
          description: بيانات غير صالحة

  # ========== معلومات النظام (System Info) ==========
  /admin/system/info:
    get:
      tags: [Info]
      summary: معلومات النظام
      description: يعرض معلومات شاملة عن PHP و Laravel والبيئة والخادم
      operationId: systemInfo
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: معلومات النظام
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    $ref: '#/components/schemas/SystemInfo'

  # ========== النسخ الاحتياطي (Backup) ==========
  /admin/system/backup:
    post:
      tags: [Backup]
      summary: إنشاء نسخة احتياطية
      description: إنشاء نسخة احتياطية جديدة لقاعدة البيانات باستخدام mysqldump
      operationId: backupCreate
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: تم إنشاء النسخة الاحتياطية
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  message:
                    type: string
                  data:
                    $ref: '#/components/schemas/BackupResource'
        '409':
          description: يوجد نسخة احتياطية قيد التشغيل
        '507':
          description: لا توجد مساحة كافية على القرص

  /admin/system/backup/list:
    get:
      tags: [Backup]
      summary: عرض النسخ الاحتياطية المتاحة
      description: يعرض قائمة بجميع ملفات النسخ الاحتياطي
      operationId: backupList
      security:
        - BearerJWT: []
        - admin: []
      responses:
        '200':
          description: قائمة النسخ الاحتياطية
          content:
            application/json:
              schema:
                type: object
                properties:
                  success:
                    type: boolean
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/BackupResource'

  /admin/system/backup/{id}/restore:
    post:
      tags: [Backup]
      summary: استعادة نسخة احتياطية
      description: استعادة قاعدة البيانات من نسخة احتياطية محددة
      operationId: backupRestore
      security:
        - BearerJWT: []
        - admin: []
      parameters:
        - $ref: '#/components/parameters/BackupId'
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required:
                - confirm
              properties:
                confirm:
                  type: boolean
                  description: تأكيد عملية الاستعادة
                  example: true
      responses:
        '200':
          description: تم استعادة قاعدة البيانات
        '422':
          description: معرف غير صالح أو لم يتم التأكيد
        '404':
          description: الملف غير موجود

  /admin/system/backup/{id}:
    delete:
      tags: [Backup]
      summary: حذف نسخة احتياطية
      description: حذف ملف النسخة الاحتياطية من القرص
      operationId: backupDelete
      security:
        - BearerJWT: []
        - admin: []
      parameters:
        - $ref: '#/components/parameters/BackupId'
      responses:
        '200':
          description: تم حذف النسخة الاحتياطية
        '422':
          description: معرف غير صالح
        '404':
          description: الملف غير موجود

# ========== أمثلة ==========
x-samples:
  curl:
    cacheClear: |
      curl -X POST https://api.beza-platform.example.com/api/v1/admin/system/cache/clear \
        -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
        -H "Accept: application/json"

    backupCreate: |
      curl -X POST https://api.beza-platform.example.com/api/v1/admin/system/backup \
        -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
        -H "Accept: application/json"

    maintenanceEnable: |
      curl -X POST https://api.beza-platform.example.com/api/v1/admin/system/maintenance \
        -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{"enabled": true, "message": "نحن تحت الصيانة", "retry": 60}'

    backupRestore: |
      curl -X POST https://api.beza-platform.example.com/api/v1/admin/system/backup/backup_2026-05-27_14-30-00.sql.gz/restore \
        -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." \
        -H "Content-Type: application/json" \
        -d '{"confirm": true}'

    backupDelete: |
      curl -X DELETE https://api.beza-platform.example.com/api/v1/admin/system/backup/backup_2026-05-27_14-30-00.sql.gz \
        -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

</div>
