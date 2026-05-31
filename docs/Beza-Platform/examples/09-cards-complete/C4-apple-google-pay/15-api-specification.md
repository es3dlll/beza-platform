# 15 - مواصفات API (API Specification)

## OpenAPI
```yaml
openapi: 3.0.0
info:
  title: Beza Wallet Pay API
  description: API لإضافة البطاقات إلى Apple Pay و Google Pay ومعالجة المدفوعات عبر المحفظة الرقمية
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
    ProvisionRequest:
      type: object
      properties:
        device_id: { type: string, example: "device-uuid-123" }
        device_name: { type: string, example: "iPhone 15 Pro" }
    WalletProvisionResponse:
      type: object
      properties:
        dpan: { type: string, example: "dpan_abc123def456" }
        token: { type: string, example: "wpt_xxxxxxxxxxxx" }
        expiry: { type: string, format: date-time }
    ChargeRequest:
      type: object
      required: [dpan, amount, merchant_id]
      properties:
        dpan: { type: string }
        amount: { type: number, minimum: 100 }
        currency: { type: string, default: SYP }
        merchant_id: { type: integer }
        description: { type: string }
    ProvisionedDevice:
      type: object
      properties:
        device_id: { type: string }
        wallet_type: { type: string, enum: [apple_pay, google_pay] }
        provisioned_at: { type: string, format: date-time }
        status: { type: string, enum: [active, removed] }
security:
  - bearerAuth: []
paths:
  /cards/{id}/wallet/apple-pay/provision:
    post:
      summary: إضافة بطاقة إلى Apple Pay
      description: إنشاء DPAN آمنة للبطاقة لإستخدامها مع Apple Pay
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ProvisionRequest'
      responses:
        '201':
          description: تم التزويد بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/WalletProvisionResponse'
        '401': { description: غير مصدَّق }
        '404': { description: بطاقة غير موجودة }
  /cards/{id}/wallet/google-pay/provision:
    post:
      summary: إضافة بطاقة إلى Google Pay
      parameters:
        - name: id
          in: path
          required: true
          schema: { type: integer }
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ProvisionRequest'
      responses:
        '201': { description: تم التزويد بنجاح }
  /wallet-pay/charge:
    post:
      summary: إجراء عملية دفع عبر المحفظة الرقمية
      description: معالجة دفع باستخدام DPAN (لا يتطلب مصادقة)
      security: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ChargeRequest'
      responses:
        '200':
          description: تمت الموافقة على الدفع
          content:
            application/json:
              schema:
                type: object
                properties:
                  success: { type: boolean, example: true }
                  transaction_id: { type: integer }
                  auth_code: { type: string }
        '400': { description: تجاوز الحد أو رصيد غير كافٍ }
        '422': { description: DPAN غير صالح }
  /cards/{id}/wallet/devices:
    get:
      summary: الأجهزة المزوَّدة
      responses:
        '200':
          description: قائمة الأجهزة
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: { type: array, items: { $ref: '#/components/schemas/ProvisionedDevice' } }
```

## cURL
```bash
# إضافة إلى Apple Pay
curl -X POST https://api.beza.sy/api/v1/cards/5/wallet/apple-pay/provision \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_id":"iphone-15-uuid"}'

# دفع عبر Wallet Pay
curl -X POST https://api.beza.sy/api/v1/wallet-pay/charge \
  -H "Content-Type: application/json" \
  -d '{"dpan":"dpan_abc123","amount":25000,"merchant_id":10}'
```
