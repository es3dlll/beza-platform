# 05 - Ù…Ù„ÙØ§Øª Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯ (Configuration Files)

## .env.example Ø¥Ù„Ù‰ .env

```bash
cp .env.example .env
php artisan key:generate
```

## Ù…Ù„Ù .env Ø§Ù„Ù…Ø¹Ø¯Ù„

```env
APP_NAME=Beza
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beza
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@beza.example"
MAIL_FROM_NAME="${APP_NAME}"

FCM_SERVER_KEY=
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=
```

## config/app.php

```php
'timezone' => 'Asia/Damascus',
'locale' => 'ar',
'faker_locale' => 'ar_SA',
```

## config/cors.php

```php
'paths' => ['api/*', 'jwt/refresh'],
'allowed_origins' => ['*'],  // ØªØ·ÙˆÙŠØ± ÙÙ‚Ø·
'supports_credentials' => true,
```

## config/jwt.php

```php
'stateful' => explode(',', env('JWT_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,localhost:5173,localhost:5174',
    env('APP_URL') ? ',' . parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),
```
