<?php

declare(strict_types=1);

use App\Modules\Marketplace\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['token.auth', 'throttle:api'])->prefix('v1/marketplace')->group(function () {
    Route::get('/products', [MarketplaceController::class, 'products']);
    Route::get('/products/{id}', [MarketplaceController::class, 'productShow']);
    Route::get('/sellers', [MarketplaceController::class, 'sellers']);
    Route::get('/sellers/{id}', [MarketplaceController::class, 'sellerShow']);
    Route::get('/stats', [MarketplaceController::class, 'stats']);

    // Admin
    Route::get('/admin/sellers', [MarketplaceController::class, 'adminSellers']);
    Route::post('/admin/sellers/{id}/approve', [MarketplaceController::class, 'approveSeller']);
    Route::post('/admin/sellers/{id}/suspend', [MarketplaceController::class, 'suspendSeller']);
});
