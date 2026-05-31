# 11 - إعدادات MySQL (MySQL Configuration)

## my.ini الأساسي

```ini
[mysqld]
# الترميز
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# المحرك الافتراضي
default-storage-engine = InnoDB

# الذاكرة
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2

# الاتصالات
max_connections = 500
wait_timeout = 600
interactive_timeout = 600

# السجلات
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2

# الأمان
local-infile = 0
skip-symbolic-links = 1

[client]
default-character-set = utf8mb4
```

## إعدادات InnoDB الهامة

| الإعداد | القيمة | التأثير |
|---------|--------|---------|
| innodb_buffer_pool_size | 70-80% من RAM | سرعة قراءة البيانات |
| innodb_log_file_size | 256M-1G | أداء الكتابة |
| innodb_flush_log_at_trx_commit | 1 (أقصى أمان) / 2 (أداء) | توازن بين الأمان والأداء |
| innodb_file_per_table | 1 | عزل جداول InnoDB |
| innodb_lock_wait_timeout | 50 | مهلة القفل |

## إدارة قواعد البيانات

```sql
-- عرض جميع قواعد البيانات
SHOW DATABASES;

-- عرض حجم قاعدة البيانات
SELECT table_schema AS 'Database',
       ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'beza'
GROUP BY table_schema;

-- عرض حالة InnoDB
SHOW ENGINE INNODB STATUS;
```
