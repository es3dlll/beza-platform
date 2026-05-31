# I1 - تشغيل البيئة المحلية

## الوصف
إعداد وتشغيل جميع مكونات المنصة محلياً.

## المتطلبات
- PHP 8.2+
- Composer
- MySQL 8.0
- Redis
- Node.js 18+
- Flutter SDK

## خطوات التشغيل

### 1. Laravel API
```bash
cd backend-laravel
composer install
cp .env.example .env   # عدّل DB_DATABASE, DB_USERNAME, DB_PASSWORD
mysql -u root -p -e "CREATE DATABASE beza"
php artisan migrate --seed
php artisan key:generate
php artisan serve --host=localhost --port=8000
php artisan queue:work   # نافذة منفصلة
```
الرابط: `http://localhost:8000/api`

### 2. Admin Dashboard
```bash
cd admin-dashboard
npm install
npm run dev
```
الرابط: `http://localhost:5173`

### 3. User Frontend
```bash
cd user-frontend
npm install
npm run dev
```
الرابط: `http://localhost:5174`

### 4. Landing Page
```bash
cd landing-page
npm install
npm run dev
```
الرابط: `http://localhost:3000`

### 5. Flutter App
```bash
cd mobile-app
flutter pub get
flutter run
```
الرابط: جهاز متصل أو Emulator

## الاختبارات
- API يعمل: GET http://localhost:8000/api/ping ← 200
- Admin Dashboard يعمل: http://localhost:5173
- User Frontend يعمل: http://localhost:5174
- Landing Page يعمل: http://localhost:3000
- Flutter App يتصل بالـ API
