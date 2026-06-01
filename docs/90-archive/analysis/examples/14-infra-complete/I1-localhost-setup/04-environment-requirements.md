# 04 - المتطلبات والأدوات (Environment Requirements)

## المتطلبات الأساسية

| الأداة | الإصدار | الغرض |
|--------|---------|-------|
| PHP | 8.2+ | تشغيل Laravel |
| Composer | 2.x | إدارة حزم PHP |
| MySQL | 8.0+ | قاعدة البيانات |
| Redis | 7.x | الكاش والجلسات وقائمة الانتظار |
| Node.js | 18+ | تشغيل الواجهات (Vite) |
| NPM | 9+ | إدارة حزم JavaScript |
| Flutter SDK | 3.x | بناء تطبيق الموبايل |
| Git | 2.x | إدارة الإصدارات |

## التحقق من التثبيت

```bash
php -v                    # PHP 8.2.x
composer --version        # Composer 2.x
mysql --version           # mysql 8.0.x
redis-server --version    # Redis 7.x
node -v                   # v18.x.x
npm -v                    # 9.x.x
flutter --version         # Flutter 3.x
git --version             # git 2.x
```

## إعدادات MySQL

```sql
CREATE DATABASE beza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'beza'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON beza.* TO 'beza'@'localhost';
FLUSH PRIVILEGES;
```

## إعدادات Redis

```bash
redis-server
# أو من Laragon: Menu → Redis → Start
```

## متغيرات البيئة (.env)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beza
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```
