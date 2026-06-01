# 11 - Redis + Queue Worker

## تكوين Redis في Laravel

```php
// config/database.php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 0,
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 1,
    ],
    'queue' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => 2,
    ],
],
```

## إعدادات البيئة

```env
SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

## إدارة Queue Worker

```bash
php artisan queue:work
php artisan queue:work redis --queue=high,default,low
php artisan queue:work redis --tries=3
php artisan queue:failed
php artisan queue:retry all
```

## اختبار Redis

```bash
redis-cli ping
# PONG
```
