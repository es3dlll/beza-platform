# 09 - حل المشكلات الشائعة (Troubleshooting Guide)

## مشاكل PHP/Composer

| المشكلة | الحل |
|---------|------|
| `composer install` يفشل | تأكد من إصدار PHP 8.2+, شغل `composer update` |
| `php artisan` لا يعمل | تأكد من وجود PHP في PATH |
| `Class not found` | شغل `composer dump-autoload` |
| `Allowed memory size exhausted` | `COMPOSER_MEMORY_LIMIT=-1 composer install` |

## مشاكل قاعدة البيانات

| المشكلة | الحل |
|---------|------|
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL لم يبدأ — شغل MySQL |
| `SQLSTATE[HY000] [1049] Unknown database` | أنشئ: `CREATE DATABASE beza` |
| `SQLSTATE[42S01] Base table already exists` | شغل `php artisan migrate:fresh` |
| `Access denied for user` | تحقق من DB_USERNAME و DB_PASSWORD في .env |

## مشاكل Redis

| المشكلة | الحل |
|---------|------|
| `Connection refused` | Redis لم يبدأ — شغل `redis-server` |
| `Protocol error, got "H" as reply` | QUEUE_CONNECTION=redis يتطلب تشغيل Redis |

## مشاكل Node.js

| المشكلة | الحل |
|---------|------|
| `npm ERR! code ENOENT` | تأكد من وجود package.json |
| `npm ERR! ERESOLVE` | شغل `npm install --legacy-peer-deps` |
| `Vite manifest not found` | شغل `npm run build` |

## مشاكل Flutter

| المشكلة | الحل |
|---------|------|
| `flutter: command not found` | أضف Flutter SDK إلى PATH |
| `Could not resolve dependencies` | شغل `flutter clean && flutter pub get` |
| `No connected devices` | شغل emulator أو صل هاتف USB |
| API connection refused on Android | استخدم `10.0.2.2` بدلا من `localhost` |

## مشاكل المنافذ

| المشكلة | الحل |
|---------|------|
| `Address already in use :8000` | غير المنفذ: `php artisan serve --port=8001` |
| `Port 5173 in use` | Vite سيطلب منفذا آخر تلقائيا |
