# 09 - العلاقات بين الجداول (Relationships)

## مخطط ER (ER Diagram) (Entity-Relationship)

```
┌───────────────┐          ┌───────────────┐          ┌──────────────────┐
│     users     │ 1      1 │   merchants   │ 1     n │ merchant_products │
│───────────────│─────────│───────────────│─────────│──────────────────│
│ id            │─────────│ id            │─────────│ id               │
│ name          │         │ user_id (FK)  │         │ merchant_id (FK) │
│ phone         │         │ business_name │         │ name             │
└───────┬───────┘         └───────────────┘         │ price            │
        │                                            └──────────────────┘
        │ 1
        │
        ├──────────────────┐
        │ 1                │ 1
  ┌─────┴──────┐     ┌─────┴──────┐
  │   wallets  │     │   agents   │
  │────────────│     │────────────│
  │ id         │     │ id         │
  │ user_id(FK)│     │ user_id(FK)│
  │ currency   │     │ agent_code │
  │ balance    │     └─────┬──────┘
  └─────┬──────┘           │ 1
        │ 1                │
        │                  │
  ┌─────┴──────────┐  ┌────┴──────────┐
  │  transactions  │  │agent_txn      │
  │────────────────│  │───────────────│
  │ id             │  │ id            │
  │from_wallet(FK) │  │ agent_id (FK) │
  │to_wallet(FK)   │  │ type          │
  │ amount         │  │ amount        │
  │ type           │  │ code          │
  └────────────────┘  └───────────────┘

┌──────────────────┐   ┌──────────────────┐   ┌──────────────────┐
│      deals       │   │ deal_investments │   │      cards       │
│──────────────────│   │──────────────────│   │──────────────────│
│ id               │──│ deal_id (FK)    │   │ id               │
│ title            │   │ user_id (FK)     │   │ user_id (FK)     │
│ target_amount    │   │ amount           │   │ card_number      │
│ roi_percentage   │   └──────────────────┘   └────────┬─────────┘
└──────────────────┘                                    │ 1
                                                         │
                                                  ┌──────┴────────┐
                                                  │card_transactions│
                                                  │────────────────│
                                                  │ card_id (FK)   │
                                                  │ amount         │
                                                  │ merchant       │
                                                  └───────────────┘
```

## ملخص العلاقات

| العلاقة | النوع | شرح |
|---------|-------|------|
| User → Wallet | 1:N | كل مستخدم يملك محفظتين (SYP, USD) |
| User → Merchant | 1:1 | مستخدم يمكن أن يكون تاجرا |
| User → Agent | 1:1 | مستخدم يمكن أن يكون وكيلا |
| User → KycDocument | 1:N | مستخدم يملك عدة وثائق KYC |
| Wallet → Transaction | 1:N | محفظة مصدر أو وجهة لعدة معاملات |
| Merchant → Product | 1:N | تاجر لديه عدة منتجات |
| Deal → Investment | 1:N | صفقة يقبلها عدة مستثمرين |
| Card → CardTransaction | 1:N | بطاقة لها عدة معاملات |
| Agent → AgentTransaction | 1:N | وكيل له عدة معاملات |
