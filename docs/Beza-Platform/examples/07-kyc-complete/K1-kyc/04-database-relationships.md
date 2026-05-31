# 04 - علاقات الجداول (Database Relationships)

## مخطط ER (ER Diagram)

```
  ┌──────────────────┐
  │      users        │
  │──────────────────│
  │ PK id             │
  │ name, phone       │
  │ kyc_status        │── not_submitted / pending / verified / rejected
  │ kyc_verified_at   │
  │ total_balance     │── لحساب حد 100 USD
  └────────┬─────────┘
           │ 1
           │ hasMany
           ▼
  ┌──────────────────────────────┐
  │      kyc_documents            │
  │──────────────────────────────│
  │ PK id                         │
  │ FK user_id                    │
  │ doc_type (ID/Passport/        │
  │          Driver_License)      │
  │ doc_category (front_id/       │
  │   back_id/selfie/address_proof)│
  │ file_path                     │
  │ file_hash (SHA256)            │
  │ mime_type                     │
  │ auto_verified (boolean)       │
  │ auto_rejection_reason (text)  │
  │ created_at                    │
  └──────────────────────────────┘

  ┌──────────────────────────────┐
  │      kyc_reviews              │
  │──────────────────────────────│
  │ PK id                         │
  │ FK user_id                    │
  │ FK reviewed_by (admin)        │
  │ status (approved/rejected)    │
  │ notes (Admin ملاحظات)         │
  │ reviewed_at                   │
  └──────────────────────────────┘
```

## SQL DDL

```sql
CREATE TABLE kyc_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    doc_type ENUM('ID','Passport','Driver_License') NOT NULL,
    doc_category ENUM('front_id','back_id','selfie','address_proof') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_hash VARCHAR(64) NOT NULL,
    mime_type VARCHAR(50) NOT NULL,
    auto_verified BOOLEAN DEFAULT FALSE,
    auto_rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    CONSTRAINT fk_kyc_doc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE kyc_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    reviewed_by BIGINT UNSIGNED NOT NULL,
    status ENUM('approved','rejected') NOT NULL,
    notes TEXT NULL,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_review_admin FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;
```
