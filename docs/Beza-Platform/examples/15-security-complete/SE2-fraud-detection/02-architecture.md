# 02 - بنية نظام كشف الاحتيال (Architecture)

## موقع النظام

```
┌──────────────────────────────────────────────────────────────────┐
│                    Client Layer (Flutter/React)                   │
│  [Transaction] → [Fraud Alert] → [Confirm/Block]                 │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
┌────────────────────────────────┴─────────────────────────────────┐
│                    Laravel Backend                                 │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              Fraud Detection Pipeline                      │   │
│  │                                                           │   │
│  │  Transaction → Pre-checks → Rules Engine → Score → Action │   │
│  │                                                           │   │
│  │  Pre-checks:                                               │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐     │   │
│  │  │ Rate     │ │ Device   │ │ IP       │ │ Amount   │     │   │
│  │  │ Limiter  │ │ Fingerprint│ │ Check    │ │ Check    │     │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘     │   │
│  │                                                           │   │
│  │  Rules Engine:                                             │   │
│  │  ┌──────────────────────────────────────────────────┐     │   │
│  │  │ Rule 1: 5+ PIN attempts → Lock                    │     │   │
│  │  │ Rule 2: >5 txn/min → 2FA                         │     │   │
│  │  │ Rule 3: New IP → SMS                             │     │   │
│  │  │ Rule 4: >5000 USD → Manual Review                 │     │   │
│  │  │ Rule 5: Repeated transfers to same → Flag        │     │   │
│  │  └──────────────────────────────────────────────────┘     │   │
│  │                                                           │   │
│  │  Score: 0-100 → None / Flag / Block                       │   │
│  │                                                           │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                       │
│  │  Redis   │  │  MySQL   │  │  Queue   │                       │
│  │ (Rate)   │  │ (Logs)   │  │ (Alerts) │                       │
│  └──────────┘  └──────────┘  └──────────┘                       │
└──────────────────────────────────────────────────────────────────┘
```

## المكونات

| المكون | التقنية | الدور |
|--------|---------|-------|
| Rate Limiter | Redis + Laravel | منع الهجمات الآلية |
| Device Fingerprint | JavaScript/Dart | تحديد الجهاز |
| IP Check | MaxMind GeoIP | موقع IP + سمعة |
| Rules Engine | PHP Service | تقييم المخاطر |
| Queue | Redis Queue | معالجة التنبيهات |
| Flagged Transactions | MySQL | تخزين المعاملات المشبوهة |
