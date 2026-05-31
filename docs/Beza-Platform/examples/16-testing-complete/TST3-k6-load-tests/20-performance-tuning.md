# 20 - تحسين الأداء (Performance Tuning)

## تحسينات Laravel

```php
<?php
// config/database.php - تحسين MySQL
'options' => [
    PDO::ATTR_EMULATE_PREPARES => true,
    PDO::MYSQL_ATTR_COMPRESS => true,     // ضغط الاتصال
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
],

// config/cache.php - استخدام Redis
'default' => env('CACHE_DRIVER', 'redis'),
'prefix' => 'beza_cache',
```

## تحسينات MySQL

```sql
-- تحسين InnoDB
SET GLOBAL innodb_buffer_pool_size = 2G;
SET GLOBAL innodb_log_file_size = 512M;
SET GLOBAL innodb_flush_log_at_trx_commit = 2;
SET GLOBAL innodb_flush_method = O_DIRECT;

-- Query cache (MySQL 5.7)
SET GLOBAL query_cache_size = 256M;
SET GLOBAL query_cache_type = 1;

-- Connections
SET GLOBAL max_connections = 500;
SET GLOBAL thread_cache_size = 100;
```

## تحسينات Nginx

```nginx
# /etc/nginx/nginx.conf
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 4096;
    multi_accept on;
    use epoll;
}

http {
    # Buffers
    client_body_buffer_size 128k;
    client_max_body_size 10m;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 8k;
    output_buffers 32 32k;
    postpone_output 1460;

    # Timeouts
    client_body_timeout 12;
    client_header_timeout 12;
    keepalive_timeout 15;
    send_timeout 10;

    # Compression
    gzip on;
    gzip_comp_level 2;
    gzip_types text/plain text/css application/json;
}
```

## تحسينات PHP-FPM

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; OpCache
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

## تحسينات Redis

```bash
# redis.conf
maxmemory 1gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
appendonly yes
appendfsync everysec
```

## قائمة تحقق K6

- [ ] استخدم `__VU` و `__ITER` لتجنب تكرار البيانات
- [ ] قلل `sleep()` في اختبارات الإجهاد
- [ ] استخدم `SharedArray` للبيانات الثابتة
- [ ] اضبط `batch` للطلبات المتوازية
- [ ] استخدم `http.batch()` للطلبات المتعددة
- [ ] راقب `http_req_waiting` (زمن الخادم الفعلي)
- [ ] اختبر من نفس المنطقة الجغرافية
