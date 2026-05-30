# Savings Backend API Specification

## Endpoint: Create Goal

```http
POST /api/v1/savings/goals
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "لابتوب جديد",
  "target_amount": 2500000,
  "target_date": "2026-12-01",
  "type": "individual",
  "icon": "phone",
  "auto_save": {
    "enabled": true,
    "frequency": "daily",
    "amount": 5000
  },
  "round_up_enabled": true,
  "goal_lock_enabled": true,
  "pin": "hashed_pin_value"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "goal_id": "goal_abc123",
    "name": "لابتوب جديد",
    "target_amount": 2500000,
    "current_amount": 0,
    "currency": "SYP",
    "type": "individual",
    "status": "active",
    "target_date": "2026-12-01",
    "auto_save": {
      "enabled": true,
      "frequency": "daily",
      "amount": 5000
    },
    "round_up_enabled": true,
    "goal_locked": true,
    "progress_pct": 0,
    "created_at": "2026-05-29T10:00:00Z"
  }
}
```

## Endpoint: List Goals

```http
GET /api/v1/savings/goals?status=active&type=individual&page=1&per_page=20
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "goals": [
      {
        "goal_id": "goal_abc123",
        "name": "لابتوب جديد",
        "target_amount": 2500000,
        "current_amount": 1250000,
        "currency": "SYP",
        "type": "individual",
        "status": "active",
        "progress_pct": 50,
        "days_remaining": 186,
        "auto_save": {
          "enabled": true,
          "frequency": "daily",
          "amount": 5000
        },
        "round_up_enabled": true,
        "goal_locked": true,
        "total_profit": 3500,
        "created_at": "2026-05-29T10:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 3,
      "last_page": 1
    },
    "summary": {
      "total_saved": 2000000,
      "total_target": 5000000,
      "total_profit": 8500,
      "active_goals": 2,
      "completed_goals": 1
    }
  }
}
```

## Endpoint: Get Goal Detail

```http
GET /api/v1/savings/goals/{id}
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "goal_id": "goal_abc123",
    "name": "لابتوب جديد",
    "target_amount": 2500000,
    "current_amount": 1250000,
    "currency": "SYP",
    "type": "individual",
    "status": "active",
    "target_date": "2026-12-01",
    "auto_save": {
      "enabled": true,
      "frequency": "daily",
      "amount": 5000,
      "next_execution": "2026-05-30T10:00:00Z"
    },
    "round_up_enabled": true,
    "goal_locked": true,
    "lock_release_date": "2026-12-01",
    "cfe_sub_account_id": "cfe_sub_789",
    "progress": {
      "percentage": 50,
      "saved": 1250000,
      "remaining": 1250000,
      "predicted_completion": "2026-11-15",
      "on_track": true
    },
    "profit": {
      "total": 3500,
      "last_distribution": {
        "amount": 1200,
        "date": "2026-05-01",
        "period": "monthly"
      }
    },
    "transactions": [
      {
        "id": "txn_001",
        "type": "deposit",
        "amount": 5000,
        "balance_before": 1245000,
        "balance_after": 1250000,
        "reference": "autosave::cfe_ref_xyz",
        "created_at": "2026-05-29T10:00:00Z"
      }
    ],
    "created_at": "2026-05-29T10:00:00Z"
  }
}
```

## Endpoint: Deposit to Goal

```http
POST /api/v1/savings/goals/{id}/deposit
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "amount": 50000,
  "pin": "hashed_pin_value"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transaction_id": "txn_dep_456",
    "goal_id": "goal_abc123",
    "type": "deposit",
    "amount": 50000,
    "balance_before": 1250000,
    "balance_after": 1300000,
    "fee": 0,
    "total_profit_earned": 3500,
    "reference": "TXN-DEP-ABC456",
    "timestamp": "2026-05-29T11:00:00Z"
  }
}
```

## Endpoint: Withdraw from Goal

```http
POST /api/v1/savings/goals/{id}/withdraw
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 500000,
  "pin": "hashed_pin_value"
}
```

### Response: 200 OK (no penalty)
```json
{
  "status": "success",
  "data": {
    "transaction_id": "txn_wd_789",
    "goal_id": "goal_abc123",
    "type": "withdrawal",
    "amount": 500000,
    "penalty": 0,
    "net_amount": 500000,
    "balance_before": 1300000,
    "balance_after": 800000,
    "reference": "TXN-WD-ABC789",
    "timestamp": "2026-05-29T11:00:00Z"
  }
}
```

### Response: 200 OK (early withdrawal penalty)
```json
{
  "status": "success",
  "data": {
    "transaction_id": "txn_wd_790",
    "goal_id": "goal_abc123",
    "type": "withdrawal",
    "amount": 500000,
    "penalty": 10000,
    "penalty_pct": 2,
    "penalty_reason": "early_withdrawal_locked_goal",
    "net_amount": 490000,
    "balance_before": 1300000,
    "balance_after": 800000,
    "reference": "TXN-WD-ABC790",
    "timestamp": "2026-05-29T11:00:00Z"
  }
}
```

## Endpoint: Update Auto-Save

```http
PUT /api/v1/savings/goals/{id}/autosave
Authorization: Bearer {token}
Content-Type: application/json

{
  "enabled": true,
  "frequency": "daily",
  "amount": 10000,
  "time": "14:00",
  "pin": "hashed_pin_value"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "goal_id": "goal_abc123",
    "auto_save": {
      "enabled": true,
      "frequency": "daily",
      "amount": 10000,
      "next_execution": "2026-05-30T14:00:00Z"
    }
  }
}
```

## Endpoint: Get Goal Progress

```http
GET /api/v1/savings/goals/{id}/progress
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "goal_id": "goal_abc123",
    "progress": {
      "percentage": 50,
      "saved": 1250000,
      "remaining": 1250000,
      "daily_required": 6720,
      "daily_auto_save": 5000,
      "daily_gap": 1720,
      "predicted_completion": "2026-11-15",
      "predicted_early_days": 16,
      "on_track": true,
      "milestones": [
        {"pct": 25, "reached": true, "reached_at": "2026-06-15"},
        {"pct": 50, "reached": true, "reached_at": "2026-07-30"},
        {"pct": 75, "reached": false, "projected": "2026-09-15"},
        {"pct": 100, "reached": false, "projected": "2026-11-15"}
      ]
    },
    "projection_chart": {
      "labels": ["يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر"],
      "projected": [300000, 600000, 900000, 1200000, 1500000, 2000000],
      "actual": [250000, 550000, 850000, 1250000, null, null],
      "target_line": 2500000
    }
  }
}
```

## Endpoint: Toggle Round-Up

```http
POST /api/v1/savings/roundup/toggle
Authorization: Bearer {token}
Content-Type: application/json

{
  "enabled": true,
  "goal_id": "goal_abc123"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "enabled": true,
    "goal_id": "goal_abc123",
    "round_up_config": {
      "min_round_amount": 100,
      "round_to_nearest": 1000,
      "total_rounded_up_today": 1500,
      "total_rounded_up_month": 25000,
      "total_rounded_up_all": 85000
    }
  }
}
```

## Endpoint: Create Team Goal

```http
POST /api/v1/savings/teams
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "رحلة العائلة",
  "target_amount": 5000000,
  "target_date": "2026-12-01",
  "pin": "hashed_pin_value"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "team_id": "team_xyz789",
    "name": "رحلة العائلة",
    "goal_id": "goal_team_456",
    "goal": {
      "target_amount": 5000000,
      "current_amount": 0,
      "status": "active"
    },
    "invite_code": "BEZA-SAVE-A3F2K9",
    "member_count": 1,
    "status": "active",
    "created_at": "2026-05-29T10:00:00Z"
  }
}
```

## Endpoint: Join Team

```http
POST /api/v1/savings/teams/{id}/join
Authorization: Bearer {token}
Content-Type: application/json

{
  "invite_code": "BEZA-SAVE-A3F2K9"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "team_id": "team_xyz789",
    "team_name": "رحلة العائلة",
    "goal": {
      "goal_id": "goal_team_456",
      "target_amount": 5000000,
      "current_amount": 2000000,
      "status": "active"
    },
    "members": [
      {"user_id": 1, "name": "أحمد", "contribution": 500000},
      {"user_id": 2, "name": "لينا", "contribution": 0, "you": true}
    ]
  }
}
```

## Error Responses

```json
// 400 — Validation Error
{
  "status": "error",
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "بيانات الإدخال غير صحيحة",
    "details": {
      "amount": ["يجب أن يكون المبلغ 1,000 ل.س على الأقل"],
      "target_amount": ["الهدف يجب أن يكون 50,000 ل.س على الأقل"],
      "name": ["اسم الهدف مطلوب"]
    }
  }
}

// 402 — Insufficient Main Wallet Balance
{
  "status": "error",
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "الرصيد في المحفظة الرئيسية غير كافٍ",
    "details": {
      "available": 25000,
      "required": 50000,
      "shortfall": 25000
    }
  }
}

// 423 — Goal Locked (Early Withdrawal)
{
  "status": "error",
  "error": {
    "code": "GOAL_LOCKED",
    "message": "الهدف مقفل حتى 2026-12-01",
    "details": {
      "lock_release_date": "2026-12-01",
      "early_withdrawal_penalty_pct": 2,
      "can_withdraw_early": true
    }
  }
}

// 409 — Duplicate Request
{
  "status": "error",
  "error": {
    "code": "DUPLICATE_REQUEST",
    "message": "تمت معالجة هذا الطلب مسبقاً",
    "details": {
      "existing_transaction_id": "txn_dep_456"
    }
  }
}

// 404 — Goal Not Found
{
  "status": "error",
  "error": {
    "code": "GOAL_NOT_FOUND",
    "message": "هدف التوفير غير موجود"
  }
}
```
