# 15 - مواصفات API كاملة (OpenAPI / Postman)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Agent Map API
  description: API للعثور على وكلاء قريبين وتحديث مواقعهم وتتبع توفرهم
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
    NearbyQuery:
      type: object
      properties:
        lat: { type: number, example: 33.5138 }
        lng: { type: number, example: 36.2765 }
        radius: { type: integer, example: 5, description: "نطاق البحث بالكيلومتر" }
        available: { type: boolean, default: true }
    NearbyAgent:
      type: object
      properties:
        id: { type: integer }
        full_name: { type: string }
        phone: { type: string }
        location_lat: { type: number }
        location_lng: { type: number }
        distance: { type: number, description: "المسافة بالكيلومتر" }
        available: { type: boolean }
        rating: { type: number }
    LocationUpdate:
      type: object
      required: [lat, lng]
      properties:
        lat: { type: number, example: 33.5200 }
        lng: { type: number, example: 36.2900 }
security:
  - bearerAuth: []
paths:
  /agents/nearby:
    get:
      summary: البحث عن وكلاء قريبين
      description: إرجاع الوكلاء المتاحين ضمن نطاق معين من الموقع المحدد
      security: []
      parameters:
        - name: lat
          in: query
          required: true
          schema: { type: number }
        - name: lng
          in: query
          required: true
          schema: { type: number }
        - name: radius
          in: query
          schema: { type: integer, default: 5 }
        - name: available
          in: query
          schema: { type: boolean, default: true }
      responses:
        '200':
          description: قائمة الوكلاء القريبين
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: { type: array, items: { $ref: '#/components/schemas/NearbyAgent' } }
        '422': { description: إحداثيات غير صالحة }
  /agents/{id}:
    get:
      summary: تفاصيل وكيل
      security: []
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      responses:
        '200':
          description: بيانات الوكيل الكاملة
        '404': { description: الوكيل غير موجود }
  /agent/location:
    put:
      summary: تحديث موقع الوكيل
      description: تحديث الإحداثيات الحالية للوكيل (لتظهر على الخريطة)
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/LocationUpdate'
      responses:
        '200': { description: تم تحديث الموقع }
        '401': { description: غير مصدَّق }
  /agent/toggle-online:
    post:
      summary: تبديل حالة التوفر
      description: تغيير حالة الوكيل بين متاح/غير متاح
      responses:
        '200':
          description: تم التبديل
          content:
            application/json:
              schema:
                type: object
                properties:
                  available: { type: boolean }
```

## cURL
```bash
# البحث عن وكلاء قريبين
curl -X GET "https://api.beza.sy/api/v1/agents/nearby?lat=33.51&lng=36.28&radius=5"

# تحديث الموقع
curl -X PUT https://api.beza.sy/api/v1/agent/location \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"lat":33.5200,"lng":36.2900}'
```
