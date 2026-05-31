# 04 - هيكل قاعدة البيانات (Database Schema)

## جدول notifications

```sql
CREATE TABLE notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type            VARCHAR(100)    NOT NULL,  -- otp, transfer_in, transfer_out, kyc, etc
    notifiable_type VARCHAR(100)    NOT NULL,  -- App\Models\User
    notifiable_id   BIGINT UNSIGNED NOT NULL,
    channel         ENUM('fcm','sms','email','database') NOT NULL,
    title           VARCHAR(255)    NULL,
    body            TEXT            NULL,
    data            JSON            NULL,       -- بيانات إضافية
    status          ENUM('pending','sent','failed','read') DEFAULT 'pending',
    sent_at         TIMESTAMP       NULL,
    read_at         TIMESTAMP       NULL,
    failed_at       TIMESTAMP       NULL,
    error_message   TEXT            NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifiable (notifiable_type, notifiable_id, status),
    INDEX idx_type_status (type, status),
    INDEX idx_created (created_at)
);
```

## جدول notification_templates

```sql
CREATE TABLE notification_templates (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type        VARCHAR(100) NOT NULL UNIQUE,
    channels    JSON         NOT NULL,       -- ['fcm','sms','email']
    title_ar    VARCHAR(255) NOT NULL,
    title_en    VARCHAR(255) NOT NULL,
    body_ar     TEXT         NOT NULL,
    body_en     TEXT         NOT NULL,
    variables   JSON         NULL,           -- ['amount','currency','name']
    priority    TINYINT      DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## جدول notification_logs

```sql
CREATE TABLE notification_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NOT NULL,
    channel         ENUM('fcm','sms','email') NOT NULL,
    provider_response JSON NULL,
    status          ENUM('sent','failed') NOT NULL,
    sent_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
);
```
