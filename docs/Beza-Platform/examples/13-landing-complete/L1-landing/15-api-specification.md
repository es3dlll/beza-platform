# 15 - مواصفات API (OpenAPI)

## OpenAPI 3.0 Specification

```yaml
openapi: 3.0.0
info:
  title: Beza Landing API
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات موقع Beza التسويقي
    نماذج الاتصال، الاشتراك في النشرة البريدية، واستفسارات التجار والوكلاء

servers:
  - url: http://localhost:8000/api
    description: Localhost Development

paths:
  /contact:
    post:
      summary: إرسال رسالة عبر نموذج الاتصال
      operationId: submitContact
      tags:
        - Landing
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ContactRequest'
      responses:
        '201':
          description: تم إرسال الرسالة بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SuccessResponse'
        '422':
          description: بيانات غير صحيحة

  /newsletter/subscribe:
    post:
      summary: الاشتراك في النشرة البريدية
      operationId: subscribeNewsletter
      tags:
        - Landing
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [email]
              properties:
                email:
                  type: string
                  format: email
                  example: user@beza.example
                name:
                  type: string
                  example: أحمد
                source:
                  type: string
                  example: footer
      responses:
        '201':
          description: تم الاشتراك بنجاح
        '422':
          description: البريد موجود بالفعل أو غير صحيح

  /newsletter/unsubscribe:
    post:
      summary: إلغاء الاشتراك في النشرة البريدية
      operationId: unsubscribeNewsletter
      tags:
        - Landing
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [email]
              properties:
                email:
                  type: string
                  format: email
      responses:
        '200':
          description: تم إلغاء الاشتراك

  /merchant-inquiry:
    post:
      summary: استفسار تاجر جديد
      operationId: merchantInquiry
      tags:
        - Landing
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/MerchantInquiryRequest'
      responses:
        '201':
          description: تم استلام الطلب

  /agent-inquiry:
    post:
      summary: استفسار وكيل جديد
      operationId: agentInquiry
      tags:
        - Landing
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/AgentInquiryRequest'
      responses:
        '201':
          description: تم استلام الطلب

components:
  schemas:
    ContactRequest:
      type: object
      required: [name, email, subject, message]
      properties:
        name:
          type: string
          maxLength: 100
          example: أحمد محمد
        email:
          type: string
          format: email
          example: ahmed@beza.example
        phone:
          type: string
          nullable: true
          example: 963944123456
        subject:
          type: string
          maxLength: 200
          example: استفسار عن الخدمات
        message:
          type: string
          minLength: 10
          maxLength: 5000
          example: أرغب في معرفة المزيد عن خدمات Beza...

    MerchantInquiryRequest:
      type: object
      required: [company_name, contact_name, email, phone]
      properties:
        company_name:
          type: string
          example: متجر النور
        contact_name:
          type: string
          example: أحمد محمد
        email:
          type: string
          format: email
        phone:
          type: string
        business_type:
          type: string
          example: ملابس
        monthly_volume:
          type: number
          example: 50000.00
        notes:
          type: string

    AgentInquiryRequest:
      type: object
      required: [company_name, contact_name, email, phone, city]
      properties:
        company_name:
          type: string
        contact_name:
          type: string
        email:
          type: string
          format: email
        phone:
          type: string
        city:
          type: string
          example: دمشق
        has_office:
          type: boolean
          default: false
        notes:
          type: string

    SuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: تم إرسال رسالتك بنجاح
        data:
          type: object
          properties:
            contact_id:
              type: integer
              example: 42
```

## أمثلة cURL

### إرسال رسالة اتصال
```bash
curl -X POST http://localhost:8000/api/contact \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد محمد",
    "email": "ahmed@beza.example",
    "subject": "استفسار",
    "message": "أرغب في معرفة المزيد عن الخدمات"
  }'
```

### الاشتراك في النشرة
```bash
curl -X POST http://localhost:8000/api/newsletter/subscribe \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "user@beza.example", "source": "footer"}'
```

### استفسار تاجر
```bash
curl -X POST http://localhost:8000/api/merchant-inquiry \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "متجر النور",
    "contact_name": "أحمد",
    "email": "ahmed@store.com",
    "phone": "963944123456",
    "business_type": "ملابس"
  }'
```
