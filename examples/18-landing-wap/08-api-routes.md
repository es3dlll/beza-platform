# مسارات API — WAP

الملف: `backend/routes/api-wap.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Wap\AuthController;
use App\Http\Controllers\Api\Wap\WalletController;
use App\Http\Controllers\Api\Wap\MerchantController;
use App\Http\Controllers\Api\Wap\AgentController;

/*
|--------------------------------------------------------------------------
| WAP API Routes — /api/v1/wap/*
|--------------------------------------------------------------------------
|
| WAP هو تطبيق ويب خفيف للجوال (PWA) بثلاث لوحات:
| مستخدم، تاجر، وكيل. كل المسارات تحت /api/v1/wap/
|
*/

Route::prefix('api/v1/wap')->group(function () {

    // ─── المصادقة (لا تحتاج Auth) ────────────────
    Route::prefix('auth')->group(function () {
        Route::post('login',  [AuthController::class, 'login']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // ─── المسارات المحمية (Auth مطلوب) ──────────
    Route::middleware('auth:wap')->group(function () {

        // المصادقة (تحتاج Auth)
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
        });

        // المحفظة
        Route::prefix('wallet')->group(function () {
            Route::get('balance',  [WalletController::class, 'balance']);
            Route::post('transfer', [WalletController::class, 'transfer']);
        });

        // التاجر
        Route::prefix('merchant')->middleware('role:merchant')->group(function () {
            Route::get('summary',     [MerchantController::class, 'summary']);
            Route::get('qr',          [MerchantController::class, 'qr']);
            Route::get('settlements', [MerchantController::class, 'settlements']);
        });

        // الوكيل
        Route::prefix('agent')->middleware('role:agent')->group(function () {
            Route::get('limits',      [AgentController::class, 'limits']);
            Route::get('commissions', [AgentController::class, 'commissions']);
            Route::get('pending',     [AgentController::class, 'pending']);
        });
    });
});
```
