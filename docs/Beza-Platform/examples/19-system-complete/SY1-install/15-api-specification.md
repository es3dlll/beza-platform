# 15 - مواصفات API (API Specification)

## OpenAPI 3.0

```yaml
openapi: 3.0.0
info:
  title: Beza Installer API
  description: واجهة تنصيب Beza — تعمل مرة واحدة فقط عند أول نشر
  version: 1.0.0

paths:
  /install:
    get:
      summary: شاشة الترحيب — التحقق من حالة المثبت
      operationId: welcome
      tags: [Installer]
      responses:
        '200':
          description: المثبت نشط وجاهز
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/WelcomeResponse'
        '403':
          description: المثبت معطل (تم التنصيب مسبقاً)
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/LockedResponse'

  /install/requirements:
    post:
      summary: فحص متطلبات الخادم
      operationId: checkRequirements
      tags: [Installer]
      responses:
        '200':
          description: نتائج الفحص
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/RequirementsResponse'

  /install/database:
    post:
      summary: اختبار اتصال MySQL وإنشاء قاعدة البيانات
      operationId: setupDatabase
      tags: [Installer]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/DatabaseRequest'
      responses:
        '200':
          description: تم الاتصال بقاعدة البيانات
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SuccessResponse'
        '422':
          description: فشل الاتصال
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /install/env:
    post:
      summary: كتابة ملف .env وتوليد المفاتيح
      operationId: configureEnv
      tags: [Installer]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/EnvironmentRequest'
      responses:
        '200':
          description: تم إعداد البيئة
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/EnvResponse'
        '500':
          description: فشل كتابة .env
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /install/migrate:
    post:
      summary: تشغيل الترحيلات والبذور
      operationId: runMigrations
      tags: [Installer]
      responses:
        '200':
          description: تم تشغيل الترحيلات
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/MigrationResponse'
        '500':
          description: فشل الترحيلات
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

  /install/admin:
    post:
      summary: إنشاء المشرف الأول
      operationId: createAdmin
      tags: [Installer]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/AdminRequest'
      responses:
        '200':
          description: تم إنشاء المشرف
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AdminCreatedResponse'
        '422':
          description: خطأ في البيانات
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ValidationError'

  /install/complete:
    post:
      summary: تعطيل المثبت وعرض ملخص التنصيب
      operationId: complete
      tags: [Installer]
      responses:
        '200':
          description: تم إكمال التنصيب
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CompleteResponse'
        '422':
          description: لم يتم إنشاء المشرف بعد
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'

components:
  schemas:
    WelcomeResponse:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "مرحباً بك في مثبت Beza" }
        data:
          type: object
          properties:
            app_name: { type: string, example: "Beza Platform" }
            php_version: { type: string, example: "8.2.0" }
            steps:
              type: array
              items: { type: string }
              example: ["requirements", "database", "environment", "migration", "admin", "complete"]

    LockedResponse:
      type: object
      properties:
        success: { type: boolean, example: false }
        message: { type: string, example: "تم إكمال التنصيب مسبقاً. المثبت معطل." }

    RequirementsResponse:
      type: object
      properties:
        success: { type: boolean, example: true }
        data:
          type: object
          properties:
            all_pass: { type: boolean, example: true }
            items:
              type: object
              additionalProperties:
                type: object
                properties:
                  pass: { type: boolean }
                  message: { type: string }
              example:
                ext_bcmath:
                  pass: true
                  message: "الإضافة BCMath — مثبتة"
                ext_redis:
                  pass: true
                  message: "الإضافة Redis — مثبتة"
                php_version:
                  pass: true
                  message: "إصدار PHP 8.2.0 — متوافق"

    DatabaseRequest:
      type: object
      required: [db_host, db_port, db_database, db_username]
      properties:
        db_host: { type: string, example: "127.0.0.1" }
        db_port: { type: integer, example: 3306 }
        db_database: { type: string, example: "beza_prod" }
        db_username: { type: string, example: "beza_user" }
        db_password: { type: string, example: "secret123" }

    EnvironmentRequest:
      type: object
      required: [app_name, app_url, app_env, redis_host, redis_port, queue_connection]
      properties:
        app_name: { type: string, example: "Beza Platform" }
        app_url: { type: string, example: "https://beza.app" }
        app_env: { type: string, enum: [local, staging, production], example: "production" }
        redis_host: { type: string, example: "127.0.0.1" }
        redis_port: { type: integer, example: 6379 }
        redis_password: { type: string, nullable: true }
        mail_host: { type: string, nullable: true, example: "smtp.gmail.com" }
        mail_port: { type: integer, nullable: true, example: 587 }
        mail_username: { type: string, nullable: true }
        mail_password: { type: string, nullable: true }
        mail_encryption: { type: string, enum: [tls, ssl, null], nullable: true }
        queue_connection: { type: string, enum: [sync, database, redis], example: "redis" }

    AdminRequest:
      type: object
      required: [name, email, phone, password, password_confirmation]
      properties:
        name: { type: string, example: "أحمد المدير" }
        email: { type: string, format: email, example: "admin@beza.app" }
        phone: { type: string, pattern: "^09[0-9]{8}$", example: "0999123456" }
        password: { type: string, minLength: 8, example: "P@ssw0rd123" }
        password_confirmation: { type: string }

    SuccessResponse:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string }

    EnvResponse:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم إعداد البيئة بنجاح" }
        data:
          type: object
          properties:
            app_key_generated: { type: boolean, example: true }
            jwt_secret_generated: { type: boolean, example: true }

    MigrationResponse:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم تشغيل الترحيلات والبذور بنجاح" }
        data:
          type: object
          properties:
            migration_output: { type: string }
            seed_output: { type: string }

    AdminCreatedResponse:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم إنشاء المشرف الأول بنجاح" }
        data:
          type: object
          properties:
            name: { type: string, example: "أحمد المدير" }
            email: { type: string, example: "admin@beza.app" }

    CompleteResponse:
      type: object
      properties:
        success: { type: boolean, example: true }
        message: { type: string, example: "تم إكمال تنصيب Beza بنجاح" }
        data:
          type: object
          properties:
            summary:
              type: object
              properties:
                app_name: { type: string }
                app_url: { type: string }
                admin_name: { type: string }
                admin_email: { type: string }
                admin_phone: { type: string }
                db_host: { type: string }
                db_name: { type: string }
                php_version: { type: string }
                completed_at: { type: string, format: date-time }
            warning: { type: string, example: "تم تعطيل المثبت. احتفظ ببيانات الدخول في مكان آمن." }

    ErrorResponse:
      type: object
      properties:
        success: { type: boolean, example: false }
        message: { type: string }

    ValidationError:
      type: object
      properties:
        success: { type: boolean, example: false }
        message: { type: string, example: "بيانات غير صحيحة" }
        errors:
          type: object
          additionalProperties:
            type: array
            items: { type: string }
```

## cURL — جميع الخطوات

```bash
# 1. الترحيب
curl http://localhost:8000/install

# 2. فحص المتطلبات
curl -X POST http://localhost:8000/install/requirements

# 3. إعداد قاعدة البيانات
curl -X POST http://localhost:8000/install/database \
  -H "Content-Type: application/json" \
  -d '{"db_host":"127.0.0.1","db_port":3306,"db_database":"beza_prod","db_username":"root","db_password":"secret"}'

# 4. إعداد البيئة
curl -X POST http://localhost:8000/install/env \
  -H "Content-Type: application/json" \
  -d '{"app_name":"Beza","app_url":"https://beza.app","app_env":"production","redis_host":"127.0.0.1","redis_port":6379,"queue_connection":"redis"}'

# 5. تشغيل الترحيلات
curl -X POST http://localhost:8000/install/migrate

# 6. إنشاء المشرف
curl -X POST http://localhost:8000/install/admin \
  -H "Content-Type: application/json" \
  -d '{"name":"أحمد","email":"admin@beza.app","phone":"0999123456","password":"P@ssw0rd123","password_confirmation":"P@ssw0rd123"}'

# 7. الإكمال
curl -X POST http://localhost:8000/install/complete
```
