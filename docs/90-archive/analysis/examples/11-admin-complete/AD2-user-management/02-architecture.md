# 02 - مكان إدارة المستخدمين في الأرشيتيكشر

```
┌──────────────────────────────────────────────────────────────┐
│                    React Admin SPA                             │
│  [UserList] → [UserManagementRepository] → [HTTP Requests]    │
└────────────────────────┬─────────────────────────────────────┘
                         │ CRUD /api/v1/admin/users/*
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                   Laravel Router (api.php)                    │
│  Route::apiResource('/admin/users', AdminUserController)      │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    Middleware Stack                            │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                    │
│  │ auth:api │  │ admin    │  │ throttle  │                    │
│  └──────────┘  └──────────┘  └──────────┘                    │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    AdminUserController                         │
│  index() → قائمة مع فلترة                                      │
│  show() → تفاصيل + محافظ + معاملات                             │
│  update() → تعديل بيانات                                       │
│  suspend() → تعليق حساب                                       │
│  activate() → تفعيل حساب                                      │
│  block() → حظر نهائي                                          │
│  destroy() → حذف ناعم                                         │
└────────────────────────┬─────────────────────────────────────┘
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                    UserManagementService                       │
│  1. Search + Filter query                                     │
│  2. Status change with validation                             │
│  3. Soft delete with SET NULL on transactions                 │
│  4. Event dispatching                                         │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         ▼
                    ┌──────────┐
                    │  MySQL   │
                    │  users   │
                    │  wallets │
                    │  trans.  │
                    └──────────┘
```

## ملفات المشروع المرتبطة

```
backend-laravel/
├── app/Http/Controllers/Api/Admin/AdminUserController.php
├── app/Http/Requests/Admin/UserFilterRequest.php
├── app/Http/Requests/Admin/UserUpdateRequest.php
├── app/Http/Resources/Admin/UserResource.php
├── app/Http/Resources/Admin/UserDetailResource.php
├── app/Services/Admin/UserManagementService.php
├── app/Exceptions/Admin/CannotDeleteSelfException.php
├── app/Exceptions/Admin/CannotSuspendAdminException.php
├── app/Events/Admin/UserSuspended.php
├── app/Events/Admin/UserActivated.php
├── app/Events/Admin/UserBlocked.php
└── app/Listeners/Admin/SendUserStatusNotification.php
```
