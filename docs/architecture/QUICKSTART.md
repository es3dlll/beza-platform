# Quick Start Guide - دليل البدء السريع

## المتطلبات

- PHP 8.3+
- Composer 2.x
- Node.js 20+
- Flutter 3.29+
- Docker (اختياري)

## المتطلبات الأساسية

- **PHP 8.3+** + Composer 2.x
- **Node.js 20+** + npm
- **Flutter 3.29+** (للموبايل)
- **Docker** (اختياري)

## تشغيل Docker (الطريقة الموصى بها)

```bash
# تشغيل كل الخدمات
docker compose up -d

# تشغيل Backend تحديداً
docker compose up -d app mysql redis rabbitmq
```

## تشغيل Backend محلياً (بدون Docker)

```bash
# المتطلبات: PHP 8.3+, Composer, MySQL, Redis
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## تشغيل Admin Panel (React 19)

```bash
cd frontend/admin
npm install
npm run dev
```

## تشغيل Mobile App (Flutter 3.29)

```bash
cd frontend/mobile
flutter pub get
flutter run
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

## ملاحظة هامة

> تم حذف الكود القديم (`backend/` و `frontend/`) في commit `a2ddf30` لإعادة البناء من الصفر.
> الأوامر أعلاه ستعمل بعد إعادة بناء المشروع. حاليًا، المنصة في مرحلة التوثيق والتصميم المعماري فقط.
> راجع [دليل البدء في التطوير](../QUICKSTART.md) للمزيد عن كيفية البدء عند بدء البرمجة.
