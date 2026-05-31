# 17 - المشاهدات والإجراءات المخزنة (Views & Procedures)

## المشاهدات (Views)

```sql
-- عرض أرصدة المستخدمين
CREATE VIEW user_balances AS
SELECT u.id, u.name, u.phone,
       COALESCE(w_usd.balance, 0) AS usd_balance,
       COALESCE(w_syp.balance, 0) AS syp_balance
FROM users u
LEFT JOIN wallets w_usd ON u.id = w_usd.user_id AND w_usd.currency = 'USD'
LEFT JOIN wallets w_syp ON u.id = w_syp.user_id AND w_syp.currency = 'SYP'
WHERE u.deleted_at IS NULL;

-- عرض ملخص المعاملات اليومية
CREATE VIEW daily_transaction_summary AS
SELECT DATE(created_at) as date,
       type,
       COUNT(*) as transaction_count,
       SUM(amount) as total_amount,
       SUM(fee) as total_fees
FROM transactions
WHERE status = 'completed'
GROUP BY DATE(created_at), type;
```

## الإجراءات المخزنة (Stored Procedures)

```sql
-- إنشاء محفظة جديدة للمستخدم
DELIMITER //
CREATE PROCEDURE CreateUserWallets(
    IN p_user_id BIGINT,
    IN p_currency_list VARCHAR(10)
)
BEGIN
    DECLARE v_currency VARCHAR(3);
    DECLARE v_wallet_number VARCHAR(20);

    SET @i = 1;
    WHILE @i <= 2 DO
        IF @i = 1 THEN SET v_currency = 'SYP';
        ELSE SET v_currency = 'USD';
        END IF;

        SET v_wallet_number = CONCAT(
            IF(v_currency = 'SYP', '62', '63'),
            LPAD(FLOOR(RAND() * 9999999999), 10, '0')
        );

        INSERT INTO wallets (user_id, currency, wallet_number, balance, frozen_balance, is_active, created_at, updated_at)
        VALUES (p_user_id, v_currency, v_wallet_number, 0, 0, 1, NOW(), NOW());

        SET @i = @i + 1;
    END WHILE;
END //
DELIMITER ;
```

## الوظائف (Functions)

```sql
-- تحويل SYP إلى USD
DELIMITER //
CREATE FUNCTION ConvertToUSD(amount_syp DECIMAL(15,2), rate DECIMAL(10,2))
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN
    RETURN ROUND(amount_syp / rate, 2);
END //
DELIMITER ;

-- توليد رقم مرجعي للمعاملة
DELIMITER //
CREATE FUNCTION GenerateReferenceNumber()
RETURNS VARCHAR(50)
DETERMINISTIC
BEGIN
    RETURN CONCAT('BZ', DATE_FORMAT(NOW(), '%y%m%d%H%i%s'), UPPER(SUBSTRING(MD5(RAND()), 1, 6)));
END //
DELIMITER ;
```

## المحفزات (Triggers)

```sql
-- تسجيل تغيير الرصيد في audit_logs
DELIMITER //
CREATE TRIGGER after_wallet_update
AFTER UPDATE ON wallets
FOR EACH ROW
BEGIN
    IF OLD.balance != NEW.balance THEN
        INSERT INTO audit_logs (event_type, loggable_type, loggable_id, user_id, data, ip, user_agent, created_at)
        VALUES ('wallet_updated', 'App\\Models\\Wallet', NEW.id, NEW.user_id,
                JSON_OBJECT('old_balance', OLD.balance, 'new_balance', NEW.balance),
                NULL, NULL, NOW());
    END IF;
END //
DELIMITER ;
```
