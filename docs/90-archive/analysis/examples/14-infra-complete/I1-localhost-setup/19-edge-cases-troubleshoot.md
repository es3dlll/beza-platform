# 19 - حالات الحافة (Edge Cases & Troubleshooting)

## 1. تعارض إصدارات PHP
```bash
"C:\php8.2\php.exe" artisan serve
```

## 2. WSL و Vite Polling
```javascript
// vite.config.js مع WSL
server: { watch: { usePolling: true, interval: 1000 } },
```

## 3. Flutter و API على Windows
```dart
const apiUrl = Platform.isAndroid
    ? 'http://10.0.2.2:8000/api/v1'
    : 'http://localhost:8000/api/v1';
```

## 4. MySQL Authentication Plugin
```sql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password';
```

## 5. Composer Memory Limit
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

## 6. Laravel Queue Worker يتوقف
```bash
php artisan queue:work --tries=1
php artisan queue:failed
```

## 7. المنافذ مشغولة
```bash
netstat -ano | findstr :8000
taskkill /PID <PID> /F
```

## 8. Flutter Build Failing
```bash
flutter clean && flutter pub cache repair && flutter pub get && flutter run
```

## 9. Git Line Endings
```
# .gitattributes
* text=auto
*.php text eol=lf
*.js text eol=lf
*.dart text eol=lf
```
