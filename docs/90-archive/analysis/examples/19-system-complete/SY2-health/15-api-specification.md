# 15 - توثيق API (OpenAPI Specification)

**الرمز التشغيلي:** SY2-health  
**النوع:** توثيق API (API Documentation)

---

## OpenAPI 3.0 Specification

```yaml
openapi: 3.0.3
info:
  title: Beza Platform - Health Check API
  description: |
    نظام التحقق الصحي المتكامل لمنصة بيزا.
    يوفر فحصاً شاملاً لجميع الخدمات الحيوية.
  version: 1.0.0
  contact:
    name: Beza Platform Team
    url: https://beza.com
  x-arabic-name: واجهة التحقق الصحي

servers:
  - url: https://api.beza.com
    description: بيئة الإنتاج
    x-arabic-description: بيئة الإنتاج
  - url: https://staging.api.beza.com
    description: بيئة الاختبار
    x-arabic-description: بيئة الاختبار

paths:
  /system/health:
    get:
      summary: الحالة الصحية العامة
      description: |
        فحص جميع الخدمات الحيوية وإرجاع تقرير شامل.
        النتائج مخزنة مؤقتاً لمدة 30 ثانية.
      operationId: getGeneralHealth
      tags:
        - Health Check
        - Public
      security: []
      parameters:
        - name: X-Debug
          in: header
          schema:
            type: boolean
          description: إذا كان true، يعيد تفاصيل إضافية (للتطوير فقط)
      responses:
        '200':
          description: تم الفحص بنجاح
          x-arabic-description: تم الفحص بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/HealthReport'
        '429':
          $ref: '#/components/responses/RateLimited'
        '503':
          description: الخدمة غير متاحة
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /system/health/db:
    get:
      summary: فحص قاعدة البيانات
      description: اختبار اتصال MySQL مع قياس زمن الاستعلام
      operationId: checkDatabase
      tags:
        - Health Check
        - Public
      security: []
      responses:
        '200':
          description: نتيجة فحص قاعدة البيانات
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SingleServiceCheck'
        '503':
          $ref: '#/components/responses/ServiceUnavailable'

  /system/health/redis:
    get:
      summary: فحص Redis
      description: اختبار الاتصال بـ Redis عبر ping
      operationId: checkRedis
      tags:
        - Health Check
        - Public
      security: []
      responses:
        '200':
          description: نتيجة فحص Redis
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SingleServiceCheck'

  /system/health/cache:
    get:
      summary: فحص الذاكرة المؤقتة
      description: اختبار كتابة وقراءة قيمة في الكاش
      operationId: checkCache
      tags:
        - Health Check
        - Public
      security: []
      responses:
        '200':
          description: نتيجة فحص الكاش
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SingleServiceCheck'

  /system/health/queue:
    get:
      summary: فحص قائمة الانتظار
      description: اختبار اتصال خدمة قوائم الانتظار
      operationId: checkQueue
      tags:
        - Health Check
        - Public
      security: []
      responses:
        '200':
          description: نتيجة فحص قائمة الانتظار
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SingleServiceCheck'

  /system/health/requirements:
    get:
      summary: فحص متطلبات PHP
      description: فحص إصدار PHP والإضافات المطلوبة
      operationId: checkRequirements
      tags:
        - Health Check
        - Public
      security: []
      responses:
        '200':
          description: نتيجة فحص متطلبات PHP
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/RequirementsCheck'

  /system/health/storage:
    get:
      summary: فحص التخزين
      description: فحص صلاحيات الكتابة للمجلدات المهمة
      operationId: checkStorage
      tags:
        - Health Check
        - Public
      security: []
      responses:
        '200':
          description: نتيجة فحص التخزين
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/StorageCheck'

  /admin/system/health:
    get:
      summary: لوحة المشرف - تقرير مفصل
      description: |
        تقرير صحي مفصل مع معلومات إضافية.
        يتطلب مصادقة JWT ودور admin.
      operationId: getAdminHealthDashboard
      tags:
        - Health Check
        - Admin
      security:
        - bearerAuth: []
      responses:
        '200':
          description: التقرير الصحي المفصل
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AdminHealthReport'
        '401':
          $ref: '#/components/responses/Unauthorized'
        '403':
          $ref: '#/components/responses/Forbidden'

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: |
        استخدم التوكن المقدم من /api/auth/login
        مع header: Authorization: Bearer {token}

  schemas:
    HealthReport:
      type: object
      x-arabic-name: تقرير صحي
      properties:
        status:
          type: string
          enum: [ok, degraded, down]
          description: الحالة العامة للنظام
          x-arabic-description: الحالة العامة للنظام
          example: ok
        services:
          type: array
          description: مصفوفة نتائج الخدمات
          x-arabic-description: مصفوفة نتائج الخدمات
          items:
            $ref: '#/components/schemas/ServiceResult'
        timestamp:
          type: string
          format: date-time
          description: وقت الفحص
          example: "2026-05-27T10:30:00Z"
        cached:
          type: boolean
          description: هل هذه نتائج مخزنة مؤقتاً
          example: false
      required:
        - status
        - services
        - timestamp

    ServiceResult:
      type: object
      x-arabic-name: نتيجة خدمة
      properties:
        name:
          type: string
          description: اسم الخدمة
          enum: [database, redis, cache, queue, storage, php_requirements]
          example: database
        status:
          type: string
          enum: [up, down, degraded]
          description: حالة الخدمة
          example: up
        latency_ms:
          type: number
          format: float
          description: زمن الاستجابة بالمللي ثانية
          example: 2.34
        details:
          type: object
          description: تفاصيل إضافية (اختياري)
        error:
          type: string
          description: رسالة الخطأ إذا كانت الخدمة معطلة (اختياري)
          example: "فشل الاتصال بقاعدة البيانات"
      required:
        - name
        - status
        - latency_ms

    SingleServiceCheck:
      type: object
      x-arabic-name: فحص خدمة واحدة
      properties:
        status:
          type: string
          enum: [up, down]
        service:
          type: string
          description: اسم الخدمة المفحوصة
        latency_ms:
          type: number
          format: float
        timestamp:
          type: string
          format: date-time
        error:
          type: string
      required:
        - status
        - service
        - timestamp

    RequirementsCheck:
      type: object
      x-arabic-name: فحص المتطلبات
      properties:
        status:
          type: string
          enum: [up, degraded]
        service:
          type: string
          example: php_requirements
        php_version:
          type: string
          example: "8.2.0"
        extensions:
          type: object
          description: حالة كل إضافة PHP
          additionalProperties:
            type: boolean
          example:
            pdo: true
            mbstring: true
            redis: true
        timestamp:
          type: string
          format: date-time

    StorageCheck:
      type: object
      x-arabic-name: فحص التخزين
      properties:
        status:
          type: string
          enum: [up, degraded]
        service:
          type: string
          example: storage
        directories:
          type: array
          items:
            type: object
            properties:
              path:
                type: string
              writable:
                type: boolean
        disk_usage:
          type: object
          properties:
            free_gb:
              type: number
            total_gb:
              type: number
            usage_percent:
              type: number
        timestamp:
          type: string
          format: date-time

    AdminHealthReport:
      type: object
      x-arabic-name: تقرير المشرف المفصل
      allOf:
        - $ref: '#/components/schemas/HealthReport'
        - type: object
          properties:
            system:
              type: object
              properties:
                php_version:
                  type: string
                laravel_version:
                  type: string
                os:
                  type: string
                memory_usage:
                  type: object
                  properties:
                    current_usage_mb:
                      type: number
                    peak_usage_mb:
                      type: number
                    memory_limit:
                      type: string
                uptime:
                  type: string

    ErrorResponse:
      type: object
      x-arabic-name: استجابة خطأ
      properties:
        status:
          type: string
          example: error
        message:
          type: string
          example: "حدث خطأ غير متوقع"
        code:
          type: integer
          example: 500
        timestamp:
          type: string
          format: date-time

  responses:
    Unauthorized:
      description: التوكن غير صالح أو منتهي
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
            example:
              status: error
              message: التوكن غير صالح
              code: 401
              timestamp: "2026-05-27T10:30:00Z"

    Forbidden:
      description: الصلاحية غير كافية
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
            example:
              status: error
              message: الصلاحية غير كافية للوصول
              code: 403
              timestamp: "2026-05-27T10:30:00Z"

    RateLimited:
      description: تجاوزت معدل الطلبات المسموح
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
            example:
              status: error
              message: تجاوزت معدل الطلبات المسموح. حاول بعد 60 ثانية
              code: 429
              timestamp: "2026-05-27T10:30:00Z"

    ServiceUnavailable:
      description: الخدمة المطلوبة غير متاحة
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'

tags:
  - name: Health Check
    description: نقاط نهاية التحقق الصحي العامة
    x-arabic-description: نقاط نهاية التحقق الصحي العامة
  - name: Admin
    description: نقاط نهاية المشرف (تتطلب صلاحيات خاصة)
    x-arabic-description: نقاط نهاية المشرف
```

---

## جدول نقاط النهاية (Endpoints Table)

| الطريقة | المسار | الباراميترز | المصادقة | التخزين المؤقت |
|---------|--------|------------|---------|---------------|
| GET | /system/health | لا يوجد | اختياري | 30 ثانية |
| GET | /system/health/db | لا يوجد | اختياري | لا |
| GET | /system/health/redis | لا يوجد | اختياري | لا |
| GET | /system/health/cache | لا يوجد | اختياري | لا |
| GET | /system/health/queue | لا يوجد | اختياري | لا |
| GET | /system/health/requirements | لا يوجد | اختياري | لا |
| GET | /system/health/storage | لا يوجد | اختياري | لا |
| GET | /admin/system/health | لا يوجد | JWT + admin | لا |

---

## رموز الحالة (Status Codes)

| الرمز (Code) | المعنى (Meaning) |
|-------------|-----------------|
| 200 | نجاح - تم الفحص وإرجاع النتائج |
| 401 | غير مصرح - التوكن غير صالح أو مفقود |
| 403 | ممنوع - الصلاحية غير كافية |
| 429 | معدل الطلبات مرتفع جداً |
| 500 | خطأ داخلي في الخادم |
| 503 | الخدمة غير متاحة مؤقتاً |
