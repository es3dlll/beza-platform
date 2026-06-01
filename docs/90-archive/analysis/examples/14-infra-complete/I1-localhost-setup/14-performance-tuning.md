# 14 - تحسين الأداء المحلي (Performance Tuning)

## تحسين PHP

```ini
; php.ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000
```

## تحسين Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

## تحسين MySQL

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 500
tmp_table_size = 64M
max_heap_table_size = 64M
```

## تحسين Redis

```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
appendonly no
```

## تحسين Vite

```javascript
// vite.config.js
server: {
    watch: { usePolling: false },
    hmr: { overlay: false },
},
build: { chunkSizeWarningLimit: 1000 },
```
