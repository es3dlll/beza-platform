# 04 - علاقات قاعدة البيانات (Database Relationships)

## مخطط ER (ER Diagram)
```
  ┌──────────────┐     ┌──────────────────────────────┐
  │    users     │────>│         merchants             │
  │──────────────│ 1   │──────────────────────────────│
  │ id           │     │ id (PK)                      │
  │ name         │     │ user_id (FK, UNIQUE)         │
  │ phone        │     │ business_name                │
  │ is_merchant  │     │ commercial_registration(UNIQUE)│
  └──────────────┘     │ tax_id (UNIQUE)              │
                        │ status (pending/active)      │
                        │ fee_percentage (2.00)       │
                        └──────────┬───────────────────┘
                                   │ 1
                        ┌──────────┴──────────┐
                        ▼                     ▼
              ┌──────────────────┐  ┌──────────────────┐
              │merchant_documents │  │ merchant_wallets  │
              │──────────────────│  │──────────────────│
              │ id               │  │ id               │
              │ merchant_id (FK) │  │ merchant_id (FK) │
              │ type             │  │ currency (SYP/USD)│
              │ file_path        │  │ wallet_number     │
              │ is_verified      │  │ balance           │
              └──────────────────┘  └──────────────────┘
```

## العلاقات (Relationships)
- **users → merchants**: واحد لواحد. كل مستخدم يمكن أن يكون له تاجر واحد فقط.
- **merchants → merchant_documents**: واحد لمتعدد. كل تاجر يرفع عدة مستندات.
- **merchants → merchant_wallets**: واحد لمتعدد. كل تاجر لديه محفظتين (SYP + USD).

## المفاتيح الفريدة (Unique Constraints)
- commercial_registration: UNIQUE لمنع تسجيل سجل تجاري مكرر
- tax_id: UNIQUE لمنع تسجيل رقم ضريبي مكرر
- user_id: UNIQUE لمنع مستخدم من إنشاء أكثر من تاجر
