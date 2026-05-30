# Loyalty Backend API Specification

## Endpoint: Get Points Balance
```http
GET /api/v1/loyalty/points
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "balance": 15000,
    "syp_value": 15000,
    "earned_today": 50,
    "earned_this_month": 1250,
    "total_earned_all_time": 150000,
    "total_redeemed_all_time": 135000,
    "points_expiring_soon": 2500,
    "expiry_date": "2027-05-15"
  }
}
```

## Endpoint: Get Current Tier
```http
GET /api/v1/loyalty/tier
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "current_tier": "silver",
    "current_tier_name": "فضي",
    "next_tier": "gold",
    "next_tier_name": "ذهبي",
    "current_points": 30000,
    "points_required": 50000,
    "progress_percentage": 60,
    "points_remaining": 20000,
    "benefits": {
      "transfer_fee": "0.4%",
      "cash_out_fee": "1.2%",
      "daily_send_limit": 1000000,
      "daily_cash_out_limit": 1000000,
      "max_balance": 5000000,
      "points_multiplier": "1.2x",
      "support_priority": "priority"
    },
    "next_tier_benefits": {
      "transfer_fee": "0.3%",
      "cash_out_fee": "1.0%",
      "daily_send_limit": 2000000,
      "points_multiplier": "1.5x",
      "fee_savings_estimate": "وفر 2,500 ل.س شهرياً"
    }
  }
}
```

## Endpoint: Get Rewards Catalog
```http
GET /api/v1/loyalty/rewards?category=fee_discount&page=1&per_page=20
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "rewards": [
      {
        "id": 1,
        "name": "خصم رسوم تحويل 5,000",
        "name_en": "Transfer Fee Discount 5,000",
        "category": "fee_discount",
        "description": "استبدل 5,000 نقطة لخصم 5,000 ل.س من رسوم التحويل",
        "point_cost": 5000,
        "syp_value": 5000,
        "image_url": "https://cdn.beza.com/rewards/fee_discount.png",
        "featured": true,
        "popular": true,
        "stock": null
      },
      {
        "id": 2,
        "name": "رصيد سيريتيل 2,500",
        "name_en": "Syriatel Airtime 2,500",
        "category": "airtime",
        "description": "اشحن رصيد سيريتيل بقيمة 2,500 ل.س",
        "point_cost": 2500,
        "syp_value": 2500,
        "provider": "SYRIATEL",
        "featured": true,
        "popular": true,
        "stock": 1000
      },
      {
        "id": 3,
        "name": "بطاقة هدايا بيمو 10,000",
        "name_en": "Bemo Gift Card 10,000",
        "category": "gift_card",
        "description": "بطاقة هدايا بيمو للتسوق بقيمة 10,000 ل.س",
        "point_cost": 10000,
        "syp_value": 10000,
        "featured": false,
        "popular": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 15,
      "last_page": 1
    },
    "categories": [
      {"key": "all", "label": "الكل"},
      {"key": "fee_discount", "label": "خصم رسوم"},
      {"key": "airtime", "label": "رصيد هاتف"},
      {"key": "gift_card", "label": "بطاقات هدايا"},
      {"key": "partner", "label": "عروض شركاء"}
    ]
  }
}
```

## Endpoint: Redeem Points
```http
POST /api/v1/loyalty/redeem
Authorization: Bearer {token}
Content-Type: application/json

{
  "reward_id": 1,
  "pin": "hashed_pin_value"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "redemption_id": "rdm_abc123",
    "reward_name": "خصم رسوم تحويل 5,000",
    "points_spent": 5000,
    "syp_value": 5000,
    "balance_after": 10000,
    "coupon_code": "CPN_FD_ABC123",
    "coupon_expires_at": "2026-07-01T10:00:00Z",
    "timestamp": "2026-06-01T10:00:00Z"
  }
}
```

### Error Responses
```json
// 402 — Insufficient Points
{
  "status": "error",
  "error": {
    "code": "INSUFFICIENT_POINTS",
    "message": "رصيد النقاط غير كافٍ",
    "message_en": "Insufficient points balance",
    "details": {
      "available": 3000,
      "required": 5000,
      "shortfall": 2000
    }
  }
}
```

## Endpoint: Get Points History
```http
GET /api/v1/loyalty/points/history?page=1&per_page=20&type=all&from=2026-01-01&to=2026-06-01
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transactions": [
      {
        "id": "pt_abc123",
        "amount": 25,
        "type": "earned",
        "source": "transfer_send",
        "source_description": "تحويل إلى أحمد",
        "tier_multiplier": 1.0,
        "expires_at": "2027-06-01T10:00:00Z",
        "created_at": "2026-06-01T10:00:00Z"
      },
      {
        "id": "pt_def456",
        "amount": -5000,
        "type": "redeemed",
        "source": "redemption",
        "source_description": "خصم رسوم تحويل 5,000",
        "created_at": "2026-05-30T14:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 250,
      "last_page": 13
    },
    "summary": {
      "total_earned": 150000,
      "total_redeemed": 135000,
      "total_expired": 5000,
      "current_balance": 10000
    }
  }
}
```

## Endpoint: Create Merchant Campaign
```http
POST /api/v1/loyalty/merchant/campaigns
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "عروض الصيف من الكترونكس",
  "type": "multiplier",
  "multiplier": 2.0,
  "min_transaction_amount": 50000,
  "budget_syp": 500000,
  "start_date": "2026-06-01",
  "end_date": "2026-07-01"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "campaign_id": "cmp_abc123",
    "name": "عروض الصيف من الكترونكس",
    "type": "multiplier",
    "status": "active",
    "budget_syp": 500000,
    "budget_remaining": 500000,
    "start_date": "2026-06-01",
    "end_date": "2026-07-01",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
