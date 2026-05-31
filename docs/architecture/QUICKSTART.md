# Quick Start Guide - دليل البدء السريع

## المتطلبات

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- Flutter 3.29+
- Docker (اختياري)

## تشغيل Backend محلياً

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## تشغيل Admin Panel

```bash
cd frontend/admin
npm install
npm run dev
```

## تشغيل Mobile App

```bash
cd frontend/mobile
flutter pub get
flutter run
```

## تشغيل Docker

```bash
docker compose up -d
```

## تشغيل الاختبارات

```bash
# Backend
cd backend && php artisan test

# Admin
cd frontend/admin && npm test

# Mobile
cd frontend/mobile && flutter test
```

## أوامر Makefile

```bash
make test        # تشغيل جميع الاختبارات
make lint        # فحص الكود
make format      # تنسيق الكود
make dev         # تشغيل بيئة التطوير
make build       # بناء للإنتاج
make deploy      # نشر للإنتاج
```
