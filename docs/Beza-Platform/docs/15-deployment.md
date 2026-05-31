# 15. التشغيل المحلي (Localhost Development Setup)

## 15.1 المتطلبات الأساسية

```
- PHP 8.2+
- Composer
- MySQL 8.0
- Redis
- Node.js 18+
- Flutter SDK (لتطبيق الجوال)
```

## 15.2 تشغيل Laravel API

```bash
# 1. الدخول إلى مجلد المشروع
cd backend-laravel

# 2. تثبيت الاعتماديات
composer install

# 3. إعداد البيئة
cp .env.example .env
# عدّل .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 4. إنشاء قاعدة البيانات
mysql -u root -p -e "CREATE DATABASE beza"

# 5. تشغيل الترحيلات والبذور
php artisan migrate --seed

# 6. إنشاء مفتاح التطبيق
php artisan key:generate

# 7. تشغيل الخادم
php artisan serve --host=localhost --port=8000

# 8. تشغيل العامل (في نافذة منفصلة)
php artisan queue:work
```

API متاح على: `http://localhost:8000/api`

## 15.3 تشغيل Admin Dashboard

```bash
cd admin-dashboard
npm install
npm run dev
```

متاح على: `http://localhost:5173`

## 15.4 تشغيل User Frontend (React SPA)

```bash
cd user-frontend
npm install
npm run dev
```

متاح على: `http://localhost:5174`

## 15.5 تشغيل Landing Page (Next.js)

```bash
cd landing-page
npm install
npm run dev
```

متاح على: `http://localhost:3000`

## 15.6 تشغيل تطبيق Flutter

```bash
cd mobile-app
flutter pub get
flutter run
```
