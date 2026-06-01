# 13 - حلول أخطاء الإعداد (Error Solutions)

## أخطاء PHP

| الخطأ | الحل |
|-------|------|
| `require(): Failed opening required vendor/autoload.php` | `composer install` |
| `Target class [Name] does not exist` | `composer dump-autoload` |
| `The only supported ciphers are AES-128-CBC and AES-256-CBC` | `php artisan key:generate` |
| `Allowed memory size exhausted` | `COMPOSER_MEMORY_LIMIT=-1 composer install` |

## أخطاء MySQL

| الخطأ | الحل |
|-------|------|
| `SQLSTATE[HY000] [1045] Access denied` | تحقق من بيانات المستخدم في .env |
| `SQLSTATE[42S02] Base table or view not found` | `php artisan migrate` |
| `The server requested authentication method unknown` | `ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password'` |

## أخطاء Redis

| الخطأ | الحل |
|-------|------|
| `Connection refused` | شغل `redis-server` أو من Laragon: Menu → Redis → Start |
| `read error on connection` | `redis-cli shutdown && redis-server` |

## أخطاء Vite/Node

| الخطأ | الحل |
|-------|------|
| `404 for @vite/client` | تأكد أن Vite يعمل في نافذة منفصلة |
| `Module not found` | `npm install` |
| `Missing "./runtime" specifier` | احذف node_modules و package-lock.json ثم `npm install` |
