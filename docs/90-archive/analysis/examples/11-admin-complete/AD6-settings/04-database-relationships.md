# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram)

```
┌──────────────────────┐
│       settings       │
│──────────────────────│
│ id (PK)              │
│ key (unique)         │
│ value (text)         │
│ type (string,number, │
│   boolean,json)      │
│ group (general,fees, │
│   limits,exchange)   │
│ description          │
│ updated_by           │
│ created_at           │
│ updated_at           │
└──────────────────────┘
```

## البيانات المخزنة

| key | group | type | default |
|-----|-------|------|---------|
| maintenance_mode | general | boolean | false |
| kyc_required | general | boolean | true |
| max_transfer_usd | limits | number | 2000 |
| max_transfer_syp | limits | number | 2000000 |
| min_deposit_usd | limits | number | 10 |
| min_deposit_syp | limits | number | 10000 |
| fee_transfer | fees | number | 0 |
| fee_exchange | fees | number | 0.5 |
| fee_card_load | fees | number | 1.5 |
| fee_merchant_percent | fees | number | 2.5 |
| fee_agent_cash_out | fees | number | 1.0 |
| exchange_rate | exchange | number | 13000 |
| exchange_margin | exchange | number | 0.5 |

## config/beza.php (القيم الافتراضية)

```php
<?php
// config/beza.php

return [
    'fees' => [
        'transfer'       => env('BEZA_FEE_TRANSFER', 0),
        'exchange'       => env('BEZA_FEE_EXCHANGE', 0.5),
        'card_load'      => env('BEZA_FEE_CARD_LOAD', 1.5),
        'merchant'       => [
            'percent' => env('BEZA_MERCHANT_PERCENT', 2.5),
            'fixed'   => env('BEZA_MERCHANT_FIXED', 0.30),
        ],
    ],
    'limits' => [
        'daily_transfer_usd' => env('BEZA_DAILY_TRANSFER_USD', 2000),
        'daily_transfer_syp' => env('BEZA_DAILY_TRANSFER_SYP', 2000000),
        'min_deposit_usd'    => env('BEZA_MIN_DEPOSIT_USD', 10),
        'min_deposit_syp'    => env('BEZA_MIN_DEPOSIT_SYP', 10000),
    ],
    'exchange' => [
        'rate'   => env('BEZA_EXCHANGE_RATE', 13000),
        'margin' => env('BEZA_EXCHANGE_MARGIN', 0.5),
    ],
    'maintenance_mode' => env('BEZA_MAINTENANCE_MODE', false),
    'kyc_required'     => env('BEZA_KYC_REQUIRED', true),
];
```

## الاستعلامات

```sql
-- قراءة جميع الإعدادات
SELECT `key`, `value`, `type` FROM settings;

-- تحديث إعداد
INSERT INTO settings (`key`, `value`, `type`, `group`, `updated_at`)
VALUES (?, ?, ?, ?, NOW())
ON DUPLICATE KEY UPDATE `value` = ?, `updated_at` = NOW();
```
