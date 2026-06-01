# 15 - مواصفات API كاملة (OpenAPI 3.0.0)

## OpenAPI 3.0 Specification

```yaml
openapi: 3.0.0
info:
  title: Beza Commodity API - الذهب والفضة
  version: 1.0.0
  description: |
    واجهة برمجة تطبيقات لشراء وبيع الذهب والفضة في منصة Beza
    - أسعار مرتبطة بالسوق العالمي (XAU/USD, XAG/USD)
    - هامش ربح 1-2%
    - متوافق مع الشريعة الإسلامية
    - تخزين في خزائن آمنة

servers:
  - url: http://localhost:8000/api/v1
    description: Local Development

paths:
  /commodity/prices:
    get:
      summary: عرض أسعار الذهب والفضة الحالية
      description: |
        يعيد آخر أسعار الذهب والفضة بالدولار والليرة السورية
        - السعر مخبأ لمدة 30 ثانية (Cache TTL)
        - يشمل سعر البيع (bid) وسعر الشراء (ask)
      operationId: getPrices
      tags:
        - Commodity
      security:
        - bearerAuth: []
      responses:
        '200':
          description: تم جلب الأسعار بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/PricesResponse'
        '401':
          description: غير مصادق

  /commodity/buy:
    post:
      summary: شراء ذهب أو فضة
      description: |
        شراء جرامات من الذهب أو الفضة
        - يُستخدم سعر ask (سعر البيع من المنصة)
        - رسوم 1.5%
        - يجب أن يكون السوق مفتوحاً
        - السعر صالح لمدة 30 ثانية
      operationId: buyCommodity
      tags:
        - Commodity
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/BuyRequest'
            example:
              commodity: "gold"
              amount_spent: 500.00
              currency: "USD"
      responses:
        '201':
          description: تم الشراء بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/BuyResponse'
        '422':
          description: فشل التحقق من البيانات
        '503':
          description: السوق مغلق حالياً

  /commodity/sell:
    post:
      summary: بيع ذهب أو فضة
      description: |
        بيع جرامات من الذهب أو الفضة
        - يُستخدم سعر bid (سعر الشراء من المستخدم)
        - رسوم 1%
        - يجب أن يكون السوق مفتوحاً
        - يجب أن تمض 24 ساعة على الأقل على آخر شراء
      operationId: sellCommodity
      tags:
        - Commodity
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/SellRequest'
            example:
              commodity: "gold"
              grams: 2.5
              currency: "USD"
      responses:
        '200':
          description: تم البيع بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/SellResponse'
        '422':
          description: فشل التحقق من البيانات أو رصيد غير كافٍ
        '503':
          description: السوق مغلق

  /commodity/holdings:
    get:
      summary: عرض محفظة الذهب والفضة
      description: |
        يعرض حيازات المستخدم مع القيمة السوقية الحالية والأرباح/الخسائر
      operationId: getHoldings
      tags:
        - Commodity
      security:
        - bearerAuth: []
      responses:
        '200':
          description: تم جلب المحفظة بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/HoldingsResponse'

  /commodity/history:
    get:
      summary: سجل معاملات الذهب والفضة
      description: سجل كامل لعمليات الشراء والبيع مع pagination
      operationId: getHistory
      tags:
        - Commodity
      security:
        - bearerAuth: []
      parameters:
        - name: page
          in: query
          schema:
            type: integer
            default: 1
        - name: per_page
          in: query
          schema:
            type: integer
            default: 20
      responses:
        '200':
          description: تم جلب السجل بنجاح
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/HistoryResponse'

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    CommodityPrice:
      type: object
      properties:
        price_usd:
          type: number
          description: سعر السلعة بالدولار
          example: 2345.50
        price_syp:
          type: number
          description: سعر السلعة بالليرة السورية
          example: 30491500.00
        bid:
          type: number
          description: سعر البيع (ما يأخذه المستخدم عند البيع)
          example: 2333.77
        ask:
          type: number
          description: سعر الشراء (ما يدفعه المستخدم عند الشراء)
          example: 2380.68
        change_24h:
          type: number
          description: التغير في آخر 24 ساعة
          example: -12.30
        timestamp:
          type: string
          format: date-time
          example: "2026-05-27T14:32:00+03:00"

    PricesResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        data:
          type: object
          properties:
            gold:
              $ref: '#/components/schemas/CommodityPrice'
            silver:
              $ref: '#/components/schemas/CommodityPrice'
        market_open:
          type: boolean
          example: true

    BuyRequest:
      type: object
      required:
        - commodity
        - amount_spent
        - currency
      properties:
        commodity:
          type: string
          enum: [gold, silver]
          description: السلعة (ذهب أو فضة)
          example: "gold"
        amount_spent:
          type: number
          description: المبلغ المراد إنفاقه
          example: 500.00
          minimum: 1
        currency:
          type: string
          enum: [SYP, USD]
          description: عملة الدفع
          example: "USD"

    BuyResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: "تم شراء 4.8544 جرام ذهب"
        data:
          type: object
          properties:
            grams:
              type: number
              example: 4.8544
            price_per_gram:
              type: number
              example: 103.00
            total_spent:
              type: number
              example: 500.00
            fee:
              type: number
              example: 7.50
            commodity:
              type: string
              example: "gold"
            holding:
              $ref: '#/components/schemas/HoldingResource'
            new_balance:
              type: number
              example: 4500.00
            reference:
              type: string
              example: "BZ2605271432A1B2C3"

    SellRequest:
      type: object
      required:
        - commodity
        - grams
        - currency
      properties:
        commodity:
          type: string
          enum: [gold, silver]
          example: "gold"
        grams:
          type: number
          description: عدد الجرامات المراد بيعها
          example: 2.5
          minimum: 0.1
        currency:
          type: string
          enum: [SYP, USD]
          example: "USD"

    SellResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        message:
          type: string
          example: "تم بيع 2.5 جرام ذهب"
        data:
          type: object
          properties:
            grams:
              type: number
              example: 2.5
            price_per_gram:
              type: number
              example: 2333.77
            total_received:
              type: number
              example: 5834.43
            fee:
              type: number
              example: 58.34
            net_received:
              type: number
              example: 5776.09
            commodity:
              type: string
              example: "gold"
            holding:
              $ref: '#/components/schemas/HoldingResource'
            new_balance:
              type: number
              example: 5776.09
            reference:
              type: string
              example: "BZ2605271543D4E5F6"

    HoldingResource:
      type: object
      properties:
        id:
          type: integer
          example: 1
        commodity:
          type: string
          example: "gold"
        grams:
          type: number
          example: 2.3544
        avg_price_usd:
          type: number
          example: 102.50
        total_invested_usd:
          type: number
          example: 241.33
        current_value_usd:
          type: number
          example: 5613.63
        profit_loss:
          type: number
          example: 5372.30
        profit_loss_percent:
          type: number
          example: 2226.12
        updated_at:
          type: string
          format: date-time

    HoldingsResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        data:
          type: array
          items:
            $ref: '#/components/schemas/HoldingResource'

    HistoryResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        data:
          type: array
          items:
            $ref: '#/components/schemas/TransactionResource'
        meta:
          type: object
          properties:
            current_page:
              type: integer
            last_page:
              type: integer
            total:
              type: integer

    TransactionResource:
      type: object
      properties:
        id:
          type: integer
          example: 42
        reference_number:
          type: string
          example: "BZ2605271432A1B2C3"
        commodity:
          type: string
          example: "gold"
        type:
          type: string
          enum: [buy, sell]
          example: "buy"
        grams:
          type: number
          example: 4.8544
        price_usd:
          type: number
          example: 103.00
        total_usd:
          type: number
          example: 500.00
        fee:
          type: number
          example: 7.50
        status:
          type: string
          enum: [pending, completed, failed, cancelled]
          example: "completed"
        created_at:
          type: string
          format: date-time

    ErrorResponse:
      type: object
      properties:
        success:
          type: boolean
          example: false
        message:
          type: string
          example: "رصيد غير كافٍ"
        errors:
          type: object
          additionalProperties:
            type: array
            items:
              type: string
```

## أمثلة cURL

### جلب الأسعار
```bash
curl -X GET http://localhost:8000/api/v1/commodity/prices \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

### شراء ذهب
```bash
curl -X POST http://localhost:8000/api/v1/commodity/buy \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "commodity": "gold",
    "amount_spent": 500,
    "currency": "USD"
  }'
```

### بيع ذهب
```bash
curl -X POST http://localhost:8000/api/v1/commodity/sell \
  -H "Accept: application/json" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{
    "commodity": "gold",
    "grams": 2.5,
    "currency": "USD"
  }'
```
