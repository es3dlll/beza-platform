# 06 - سكريبتات التشغيل الآلي (Shell Scripts)

## setup.bat (Windows)

```batch
@echo off
echo === Beza Platform Setup ===

echo [1/8] تثبيت حزم PHP...
cd backend-laravel
call composer install
if %errorlevel% neq 0 exit /b %errorlevel%

echo [2/8] إعداد .env...
if not exist .env (
    copy .env.example .env
    php artisan key:generate
)

echo [3/8] تشغيل الترحيلات...
php artisan migrate --seed
if %errorlevel% neq 0 exit /b %errorlevel%

echo [4/8] تثبيت Admin Dashboard...
cd ../admin-dashboard
call npm install
if %errorlevel% neq 0 exit /b %errorlevel%

echo [5/8] تثبيت User Frontend...
cd ../user-frontend
call npm install
if %errorlevel% neq 0 exit /b %errorlevel%

echo [6/8] تثبيت Landing Page...
cd ../landing-page
call npm install
if %errorlevel% neq 0 exit /b %errorlevel%

echo [7/8] تثبيت Flutter...
cd ../mobile-app
call flutter pub get
if %errorlevel% neq 0 exit /b %errorlevel%

echo [8/8] تم الإعداد بنجاح!
cd ..
```

## start-all.bat

```batch
@echo off
echo === تشغيل جميع المشاريع ===

start "Laravel API" cmd /c "cd backend-laravel && php artisan serve --host=localhost --port=8000"
start "Queue Worker" cmd /c "cd backend-laravel && php artisan queue:work"
start "Admin Dashboard" cmd /c "cd admin-dashboard && npm run dev"
start "User Frontend" cmd /c "cd user-frontend && npm run dev"
start "Landing Page" cmd /c "cd landing-page && npm run dev"
start "Flutter App" cmd /c "cd mobile-app && flutter run"

echo === جميع المشاريع تعمل ===
echo API: http://localhost:8000
echo Admin: http://localhost:5173
echo User: http://localhost:5174
echo Landing: http://localhost:3000
```

## verify.bat

```batch
@echo off
echo === التحقق من الخدمات ===

curl -s http://localhost:8000/api/ping >nul && echo [OK] API يعمل
curl -s http://localhost:5173 >nul && echo [OK] Admin Dashboard يعمل
curl -s http://localhost:5174 >nul && echo [OK] User Frontend يعمل
curl -s http://localhost:3000 >nul && echo [OK] Landing Page يعمل
```
