# 12 - إعداد Nginx للوكيل العكسي (Nginx Configuration)

## nginx.conf للتطوير

```nginx
server {
    listen 80;
    server_name beza.local;

    location /api {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /admin {
        proxy_pass http://localhost:5173;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    location /app {
        proxy_pass http://localhost:5174;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    location / {
        proxy_pass http://localhost:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    location /storage {
        alias /var/www/backend-laravel/storage/app/public;
    }
}
```

## بديل Apache (Laragon الافتراضي)

Laragon يستخدم Apache افتراضياً. تكفي إضافة ملف `.htaccess` في مجلد المشروع:

```apache
RewriteEngine On

# API
RewriteRule ^api/(.*) http://localhost:8000/$1 [P,L]

# Admin
RewriteRule ^admin/(.*) http://localhost:5173/$1 [P,L]

# User SPA
RewriteRule ^app/(.*) http://localhost:5174/$1 [P,L]

# Landing
RewriteCond %{REQUEST_URI} !^/(api|admin|app)
RewriteRule ^(.*) http://localhost:3000/$1 [P,L]
```

أو استخدم Virtual Hosts من Laragon مباشرة:
1. Laragon → Menu → Apache → Virtual Hosts
2. Domain: `beza.test`, Folder: `C:\laragon\www\Beza-Platform`

## إعداد hosts

```
# أضف إلى C:\Windows\System32\drivers\etc\hosts
127.0.0.1 beza.local
127.0.0.1 beza.test
```
