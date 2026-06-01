# 02 - مكان العملية في الأرشيتيكشر (Architecture) — تنصيب النظام (First-Run Installer)

## موقع العملية ضمن طبقات النظام

```
┌──────────────────────────────────────────────────────────────────────┐
│                         المتصفح (React SPA)                          │
│  [InstallerWizard] → [7 خطوات] → [Requests HTTP]                     │
└─────────────────────────────────┬────────────────────────────────────┘
                                  │ GET /install, POST /install/*
                                  ▼
┌──────────────────────────────────────────────────────────────────────┐
│                      Laravel Router (web.php)                         │
│  Route::get('/install', ...) → InstallerController                   │
│  Route::post('/install/*', ...) → InstallerController                │
│  Middleware: web + CheckInstallerEnabled                             │
└─────────────────────────────────┬────────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────────┐
│                   InstallerController                                │
│  ├── welcome()        → GET  /install                                │
│  ├── checkRequirements() → POST /install/requirements                │
│  ├── setupDatabase()  → POST /install/database                       │
│  ├── configureEnv()   → POST /install/env                            │
│  ├── runMigrations()  → POST /install/migrate                        │
│  ├── createAdmin()    → POST /install/admin                          │
│  └── complete()       → POST /install/complete                       │
└─────────────────────────────────┬────────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────────┐
│                    طبقة الخدمات (Services)                            │
│  ┌──────────────────────┐  ┌──────────────────────────────────────┐  │
│  │  RequirementChecker  │  │  EnvironmentConfigurator             │  │
│  │  ├─ checkPhpExtensions│  │  ├─ readEnv()                      │  │
│  │  ├─ checkMySQL()     │  │  ├─ writeEnv()                      │  │
│  │  ├─ checkRedis()     │  │  ├─ generateAppKey()                │  │
│  │  └─ checkCommands()  │  │  └─ generateJwtSecret()             │  │
│  └──────────────────────┘  └──────────────────────────────────────┘  │
└─────────────────────────────────┬────────────────────────────────────┘
                                  ▼
┌──────────────────────────────────────────────────────────────────────┐
│                    النظام الخارجي (Artisan)                           │
│  ├── php artisan key:generate                                        │
│  ├── php artisan jwt:secret                                          │
│  ├── php artisan migrate --force                                     │
│  └── php artisan db:seed --force                                     │
└──────────────────────────────────────────────────────────────────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Install/InstallerController.php
├── app/Http/Requests/Install/
│   ├── DatabaseRequest.php
│   ├── EnvironmentRequest.php
│   └── AdminUserRequest.php
├── app/Services/Install/
│   ├── RequirementChecker.php
│   └── EnvironmentConfigurator.php
├── app/Events/InstallationCompleted.php
├── app/Http/Middleware/CheckInstallerEnabled.php
├── app/Providers/InstallServiceProvider.php
├── resources/views/install/
│   └── index.blade.php (أو React SPA)
└── routes/web.php
```
