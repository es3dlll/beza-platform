# Backend API — Marketplace Endpoints

## Base URL

```
Production: https://api.beza.sy/marketplace/v1
Sandbox:   https://sandbox.api.beza.sy/marketplace/v1
```

## Authentication

All endpoints require Bearer token authentication (JWT) unless marked as public.

```
Authorization: Bearer <jwt_token>
```

## Rate Limiting

| Endpoint Group | Limit |
|---|---|
| Product search (public) | 100 req/min per IP |
| Order creation | 20 req/min per user |
| Top-up | 10 req/min per user |
| Gift card purchase | 10 req/min per user |
| Admin endpoints | 200 req/min per admin |

## Common Response Format

```json
{
  "success": true,
  "data": {},
  "meta": {
    "page": 1,
    "perPage": 20,
    "total": 100,
    "timestamp": "2026-05-29T10:30:00+03:00"
  },
  "error": null
}
```

Error response:
```json
{
  "success": false,
  "data": null,
  "meta": null,
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "رصيد المحفظة غير كافٍ",
    "details": {
      "walletBalance": "5000",
      "requiredAmount": "10000"
    }
  }
}
```

---

## Product Catalog

### GET /marketplace/products

Retrieve products with search, filtering, and pagination.

**Query Parameters**:
| Parameter | Type | Required | Description |
|---|---|---|---|
| `category` | string | No | Category slug |
| `q` | string | No | Full-text search query |
| `vendor` | string | No | Vendor ID |
| `minPrice` | number | No | Minimum price (SYP) |
| `maxPrice` | number | No | Maximum price (SYP) |
| `rating` | number | No | Minimum rating (1–5) |
| `sort` | string | No | Sort: `price_asc`, `price_desc`, `rating`, `newest`, `popular` |
| `page` | number | No | Page number (default: 1) |
| `perPage` | number | No | Items per page (default: 20, max: 50) |
| `inStock` | boolean | No | Filter by stock availability |

**Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": "prod_abc123",
      "title": "600 UC + 60 Bonus PUBG Mobile",
      "slug": "pubg-600-uc-bonus",
      "description": "600 UC + 60 مكافأة لحساب PUBG Mobile",
      "category": {
        "id": "cat_digital_games",
        "name": "ألعاب",
        "slug": "digital-games"
      },
      "vendor": {
        "id": "ven_xyz789",
        "name": "بي سي ماركت",
        "rating": 4.8,
        "orderCount": 15230
      },
      "price": 28000,
      "currency": "SYP",
      "compareAtPrice": 35000,
      "stock": 500,
      "images": [
        "https://cdn.beza.sy/products/pubg-600uc-v1.jpg"
      ],
      "rating": 4.8,
      "reviewCount": 2340,
      "deliveryType": "INSTANT_CODE",
      "badges": ["BESTSELLER", "TOP_RATED"],
      "createdAt": "2026-01-15T00:00:00+03:00"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 20,
    "total": 156,
    "totalPages": 8
  },
  "error": null
}
```

---

### GET /marketplace/products/:slug

Retrieve a single product by slug (SEO-friendly) or ID.

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "id": "prod_abc123",
    "title": "600 UC + 60 Bonus PUBG Mobile",
    "slug": "pubg-600-uc-bonus",
    "description": "600 UC + 60 مكافأة لحساب PUBG Mobile",
    "longDescription": "احصل على 600 UC أساسي + 60 UC إضافي مجاناً...",
    "terms": "الرمز صالح لمدة 30 يوم من تاريخ الإصدار",
    "category": { "id": "cat_digital_games", "name": "ألعاب" },
    "vendor": {
      "id": "ven_xyz789",
      "name": "بي سي ماركت",
      "rating": 4.8,
      "orderCount": 15230,
      "avatar": "https://cdn.beza.sy/vendors/pc-market-logo.png",
      "responseTime": "خلال 5 دقائق"
    },
    "price": 28000,
    "currency": "SYP",
    "compareAtPrice": 35000,
    "commission": {
      "rate": 12,
      "amount": 3360
    },
    "stock": 500,
    "images": ["https://cdn.beza.sy/products/pubg-600uc-v1.jpg"],
    "attributes": {
      "platform": "PUBG Mobile",
      "region": "Global",
      "delivery": "فوري"
    },
    "rating": 4.8,
    "reviewCount": 2340,
    "reviews": [
      {
        "id": "rev_001",
        "user": "L***a",
        "rating": 5,
        "comment": "سريع جداً والرمز اشتغل فوراً",
        "date": "2026-05-28"
      }
    ],
    "deliveryType": "INSTANT_CODE",
    "badges": ["BESTSELLER", "TOP_RATED"],
    "createdAt": "2026-01-15T00:00:00+03:00"
  },
  "error": null
}
```

---

### GET /marketplace/categories

Retrieve category tree.

**Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "id": "cat_topup",
      "name": "شحن رصيد",
      "slug": "mobile-topup",
      "icon": "https://cdn.beza.sy/icons/topup.svg",
      "children": [
        { "id": "cat_topup_syriatel", "name": "سيريتل", "slug": "syriatel-topup" },
        { "id": "cat_topup_mtn", "name": "MTN", "slug": "mtn-topup" }
      ]
    },
    {
      "id": "cat_giftcards",
      "name": "بطاقات هدايا",
      "slug": "gift-cards",
      "icon": "https://cdn.beza.sy/icons/giftcard.svg"
    },
    {
      "id": "cat_digital",
      "name": "سلع رقمية",
      "slug": "digital-goods",
      "icon": "https://cdn.beza.sy/icons/digital.svg"
    }
  ],
  "error": null
}
```

---

## Mobile Top-Up

### POST /marketplace/top-up

Perform a mobile top-up.

**Request Body**:
```json
{
  "phoneNumber": "0933456789",
  "network": "syriatel",
  "amount": 10000,
  "saveRecipient": true,
  "recipientName": "أمي",
  "scheduledAt": null
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `phoneNumber` | string | Yes | Phone number (09XX-XXX-XXX format) |
| `network` | string | Yes | `syriatel` or `mtn` |
| `amount` | number | Yes | Amount in SYP (min: 250, max: 25000) |
| `saveRecipient` | boolean | No | Save as favorite recipient |
| `recipientName` | string | No | Nickname for saved recipient |
| `scheduledAt` | ISO8601 | No | Schedule future top-up |

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "orderId": "MKT-2026-05189",
    "transactionId": "txn_syr_a7f3k2",
    "network": "syriatel",
    "phoneNumber": "0933456789",
    "amount": 10000,
    "fee": 0,
    "total": 10000,
    "status": "COMPLETED",
    "walletBalanceAfter": 35300,
    "deliveredAt": "2026-05-29T10:30:05+03:00"
  },
  "error": null
}
```

**Error Responses**:
| Code | HTTP Status | Message |
|---|---|---|
| INVALID_NUMBER | 400 | رقم الهاتف غير صحيح |
| NETWORK_MISMATCH | 400 | رقم الهاتف لا يتطابق مع الشبكة المحددة |
| INSUFFICIENT_BALANCE | 400 | رصيد المحفظة غير كافٍ |
| AMOUNT_OUT_OF_RANGE | 400 | المبلغ خارج النطاق المسموح |
| TELECOM_UNAVAILABLE | 503 | خدمة الشحن غير متاحة حالياً، يرجى المحاولة لاحقاً |
| DUPLICATE_REQUEST | 409 | تمت معالجة هذا الطلب مسبقاً |

---

### GET /marketplace/top-up/plans

Retrieve available top-up denominations and data plans for a network.

**Query Parameters**:
| Parameter | Type | Required | Description |
|---|---|---|---|
| `network` | string | Yes | `syriatel` or `mtn` |

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "network": "syriatel",
    "presetAmounts": [250, 500, 1000, 2500, 5000, 10000, 25000],
    "customAmount": {
      "min": 100,
      "max": 50000,
      "step": 100
    },
    "dataPlans": [
      {
        "id": "plan_syr_1gb",
        "name": "باقة 1 جيجابايت",
        "price": 3500,
        "validity": "7 أيام",
        "data": "1 GB"
      },
      {
        "id": "plan_syr_5gb",
        "name": "باقة 5 جيجابايت",
        "price": 12000,
        "validity": "30 يوم",
        "data": "5 GB"
      }
    ]
  },
  "error": null
}
```

---

## Orders

### POST /marketplace/orders

Create a new order from cart items.

**Request Body**:
```json
{
  "items": [
    {
      "productId": "prod_abc123",
      "quantity": 1
    },
    {
      "productId": "prod_def456",
      "quantity": 2
    }
  ],
  "promoCode": "WELCOME10",
  "notes": "يرجى إرسال الرمز عبر SMS أيضاً"
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `items` | array | Yes | Array of product ID + quantity |
| `promoCode` | string | No | Promotional discount code |
| `notes` | string | No | Order notes for vendor |

**Response (201)**:
```json
{
  "success": true,
  "data": {
    "orderId": "MKT-2026-05190",
    "status": "PROCESSING",
    "items": [
      {
        "productId": "prod_abc123",
        "title": "600 UC + 60 Bonus PUBG Mobile",
        "quantity": 1,
        "unitPrice": 28000,
        "totalPrice": 28000,
        "deliveryType": "INSTANT_CODE",
        "fulfillmentStatus": "PENDING"
      },
      {
        "productId": "prod_def456",
        "title": "Free Fire 100 Diamonds",
        "quantity": 2,
        "unitPrice": 12500,
        "totalPrice": 25000,
        "deliveryType": "INSTANT_CODE",
        "fulfillmentStatus": "PENDING"
      }
    ],
    "subtotal": 53000,
    "discount": 5300,
    "commission": 6360,
    "total": 47700,
    "walletHold": 47700,
    "currency": "SYP",
    "createdAt": "2026-05-29T10:31:00+03:00"
  },
  "error": null
}
```

**Error Responses**:
| Code | HTTP Status | Message |
|---|---|---|
| PRODUCT_NOT_FOUND | 404 | المنتج غير موجود |
| OUT_OF_STOCK | 400 | المنتج نفد من المخزون |
| INSUFFICIENT_BALANCE | 400 | رصيد المحفظة غير كافٍ |
| INVALID_PROMO | 400 | رمز الخصم غير صالح |
| PRICE_CHANGED | 409 | تغير سعر المنتج، يرجى مراجعة السلة |

---

### GET /marketplace/orders

Retrieve the authenticated user's orders.

**Query Parameters**:
| Parameter | Type | Required | Description |
|---|---|---|---|
| `status` | string | No | Filter by status: `pending`, `processing`, `completed`, `cancelled` |
| `page` | number | No | Page number |
| `perPage` | number | No | Items per page |

**Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "orderId": "MKT-2026-05190",
      "status": "PROCESSING",
      "items": [
        {
          "title": "600 UC + 60 Bonus PUBG Mobile",
          "quantity": 1,
          "totalPrice": 28000,
          "fulfillmentStatus": "PENDING"
        }
      ],
      "total": 47700,
      "currency": "SYP",
      "itemCount": 3,
      "createdAt": "2026-05-29T10:31:00+03:00",
      "statusBadge": {
        "label": "قيد المعالجة",
        "color": "blue"
      }
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 20,
    "total": 45,
    "totalPages": 3
  },
  "error": null
}
```

---

### GET /marketplace/orders/:orderId

Retrieve a single order by ID.

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "orderId": "MKT-2026-05190",
    "status": "PROCESSING",
    "items": [
      {
        "itemId": "item_001",
        "productId": "prod_abc123",
        "title": "600 UC + 60 Bonus PUBG Mobile",
        "image": "https://cdn.beza.sy/products/pubg-600uc-v1.jpg",
        "quantity": 1,
        "unitPrice": 28000,
        "totalPrice": 28000,
        "deliveryType": "INSTANT_CODE",
        "fulfillmentStatus": "DELIVERED",
        "deliveryDetails": {
          "code": "PUBG-X7K9-M2N4",
          "codeType": "activation_code",
          "deliveredAt": "2026-05-29T10:31:05+03:00"
        }
      }
    ],
    "subtotal": 53000,
    "discount": 5300,
    "commission": 6360,
    "total": 47700,
    "currency": "SYP",
    "payment": {
      "method": "wallet",
      "status": "HELD",
      "holdId": "hold_z9y8x7",
      "heldAt": "2026-05-29T10:31:00+03:00"
    },
    "timeline": [
      { "status": "CREATED", "timestamp": "2026-05-29T10:31:00+03:00" },
      { "status": "HOLD_PLACED", "timestamp": "2026-05-29T10:31:01+03:00" },
      { "status": "PROCESSING", "timestamp": "2026-05-29T10:31:02+03:00" }
    ],
    "createdAt": "2026-05-29T10:31:00+03:00"
  },
  "error": null
}
```

---

### DELETE /marketplace/orders/:orderId

Cancel an order (only if status is PENDING or PROCESSING).

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "orderId": "MKT-2026-05190",
    "status": "CANCELLED",
    "refundAmount": 47700,
    "refundedAt": "2026-05-29T10:45:00+03:00"
  },
  "error": null
}
```

---

## Gift Cards

### GET /marketplace/gift-cards

Retrieve the authenticated user's gift cards.

**Query Parameters**:
| Parameter | Type | Required | Description |
|---|---|---|---|
| `status` | string | No | `active`, `redeemed`, `expired`, `cancelled` |
| `page` | number | No | Page number |

**Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "cardId": "gift_a1b2c3",
      "code": "GC-7K9M-X2N4-P8Q1",
      "merchant": {
        "id": "merch_001",
        "name": "متجر الألكترونيات",
        "logo": "https://cdn.beza.sy/merchants/electro-logo.png"
      },
      "initialBalance": 50000,
      "remainingBalance": 50000,
      "currency": "SYP",
      "status": "ACTIVE",
      "expiresAt": "2027-05-29T00:00:00+03:00",
      "purchasedAt": "2026-05-29T10:00:00+03:00",
      "recipient": {
        "name": "سارة",
        "phone": "0955123456"
      },
      "qrCodeUrl": "https://cdn.beza.sy/gift-cards/qr/gift_a1b2c3.png"
    }
  ],
  "meta": {
    "page": 1,
    "perPage": 20,
    "total": 5,
    "totalPages": 1
  },
  "error": null
}
```

---

### POST /marketplace/gift-cards

Purchase one or more gift cards.

**Request Body**:
```json
{
  "merchantId": "merch_001",
  "denomination": 50000,
  "quantity": 1,
  "recipient": {
    "name": "سارة",
    "phone": "0955123456",
    "email": null
  },
  "deliveryMethod": "whatsapp",
  "personalMessage": "عيد مبارك يا سارة! 🎉",
  "scheduledDelivery": null
}
```

**Response (201)**:
```json
{
  "success": true,
  "data": {
    "orderId": "MKT-2026-05191",
    "cards": [
      {
        "cardId": "gift_a1b2c3",
        "code": "GC-7K9M-X2N4-P8Q1",
        "merchant": "متجر الألكترونيات",
        "denomination": 50000,
        "status": "ACTIVE"
      }
    ],
    "total": 50000,
    "commission": 4000,
    "walletDeduction": 50000,
    "deliveryStatus": "SENT",
    "deliveryChannel": "whatsapp"
  },
  "error": null
}
```

---

### POST /marketplace/gift-cards/redeem

Redeem a gift card at a merchant or to wallet.

**Request Body**:
```json
{
  "code": "GC-7K9M-X2N4-P8Q1",
  "merchantId": "merch_001",
  "redeemAmount": 50000
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `code` | string | Yes | 16-digit gift card code |
| `merchantId` | string | Yes | Merchant where redeeming |
| `redeemAmount` | number | No | Partial amount (defaults to full balance) |

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "cardId": "gift_a1b2c3",
    "code": "GC-7K9M-X2N4-P8Q1",
    "merchant": "متجر الألكترونيات",
    "redeemedAmount": 50000,
    "remainingBalance": 0,
    "status": "REDEEMED",
    "redeemedAt": "2026-05-29T11:00:00+03:00",
    "transactionId": "red_xyz789"
  },
  "error": null
}
```

**Error Responses**:
| Code | HTTP Status | Message |
|---|---|---|
| CARD_NOT_FOUND | 404 | بطاقة الهدية غير موجودة |
| CARD_EXPIRED | 400 | بطاقة الهدية منتهية الصلاحية |
| CARD_ALREADY_REDEEMED | 400 | تم استعمال بطاقة الهدية مسبقاً |
| INVALID_MERCHANT | 400 | المتجر غير صالح لهذه البطاقة |
| INSUFFICIENT_BALANCE | 400 | الرصيد المتبقي في البطاقة غير كافٍ |

---

### GET /marketplace/gift-cards/:cardId

Retrieve a single gift card detail.

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "cardId": "gift_a1b2c3",
    "code": "GC-7K9M-X2N4-P8Q1",
    "merchant": {
      "id": "merch_001",
      "name": "متجر الألكترونيات",
      "logo": "https://cdn.beza.sy/merchants/electro-logo.png",
      "website": "https://electro.sy"
    },
    "initialBalance": 50000,
    "remainingBalance": 25000,
    "currency": "SYP",
    "status": "ACTIVE",
    "expiresAt": "2027-05-29T00:00:00+03:00",
    "purchasedAt": "2026-05-29T10:00:00+03:00",
    "redemptionHistory": [
      {
        "merchant": "متجر الألكترونيات",
        "amount": 25000,
        "date": "2026-05-30T14:00:00+03:00"
      }
    ],
    "recipient": {
      "name": "سارة",
      "phone": "0955123456"
    },
    "qrCodeUrl": "https://cdn.beza.sy/gift-cards/qr/gift_a1b2c3.png"
  },
  "error": null
}
```

---

## Vendor Endpoints

### POST /marketplace/vendor/products

Create a new product listing (vendor auth required).

**Request Body**:
```json
{
  "categoryId": "cat_digital_games",
  "title": "600 UC + 60 Bonus PUBG Mobile",
  "description": "600 UC + 60 مكافأة",
  "longDescription": "...",
  "price": 28000,
  "compareAtPrice": 35000,
  "stock": 500,
  "images": ["base64_encoded_image..."],
  "deliveryType": "INSTANT_CODE",
  "attributes": {
    "platform": "PUBG Mobile",
    "region": "Global"
  }
}
```

**Response (201)**:
```json
{
  "success": true,
  "data": {
    "productId": "prod_abc123",
    "status": "PENDING_MODERATION",
    "estimatedModerationTime": "خلال ٢٤ ساعة"
  },
  "error": null
}
```

---

### GET /marketplace/vendor/earnings

Retrieve vendor earnings and commission summary.

**Query Parameters**:
| Parameter | Type | Required | Description |
|---|---|---|---|
| `period` | string | No | `today`, `week`, `month`, `all` (default: `month`) |

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "totalRevenue": 12500000,
    "totalCommissions": 1500000,
    "netEarnings": 11000000,
    "pendingPayout": 3500000,
    "nextPayoutDate": "2026-06-05",
    "periodBreakdown": {
      "today": { "revenue": 450000, "commission": 54000 },
      "thisWeek": { "revenue": 2800000, "commission": 336000 },
      "thisMonth": { "revenue": 12500000, "commission": 1500000 }
    }
  },
  "error": null
}
```

---

### GET /marketplace/vendor/products

List vendor's own products (vendor auth).

**Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "productId": "prod_abc123",
      "title": "600 UC + 60 Bonus PUBG Mobile",
      "price": 28000,
      "stock": 500,
      "sales": 2340,
      "status": "ACTIVE",
      "moderationStatus": "APPROVED",
      "createdAt": "2026-01-15T00:00:00+03:00"
    }
  ],
  "error": null
}
```

---

## Admin Endpoints

### GET /marketplace/admin/vendors/pending

List pending vendor applications.

**Response (200)**:
```json
{
  "success": true,
  "data": [
    {
      "applicationId": "app_001",
      "businessName": "متجر الألعاب السوري",
      "ownerName": "خالد الأحمد",
      "phone": "0933123456",
      "email": "khalid@gameshop.sy",
      "category": "digital_goods",
      "submittedAt": "2026-05-28T10:00:00+03:00",
      "documents": [
        { "type": "license", "url": "https://cdn.beza.sy/docs/license_001.pdf" }
      ]
    }
  ],
  "error": null
}
```

---

### PUT /marketplace/admin/products/:productId/moderate

Approve or reject a product.

**Request Body**:
```json
{
  "action": "approve",
  "reason": null
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `action` | string | Yes | `approve` or `reject` |
| `reason` | string | No | Rejection reason (required if reject) |

**Response (200)**:
```json
{
  "success": true,
  "data": {
    "productId": "prod_abc123",
    "status": "ACTIVE",
    "moderatedAt": "2026-05-29T12:00:00+03:00"
  },
  "error": null
}
```
