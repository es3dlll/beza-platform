<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Controllers\ApiController;
use Modules\Marketplace\Controllers\CodController;
use Modules\Marketplace\Controllers\GiftCardController;
use Modules\Marketplace\Controllers\LoyaltyController;
use Modules\Marketplace\Controllers\OrderController;
use Modules\Marketplace\Controllers\ProductController;
use Modules\Marketplace\Controllers\PromoController;
use Modules\Marketplace\Controllers\ShippingController;
use Modules\Marketplace\Controllers\VendorController;
use Modules\Marketplace\Services\CatalogService;

Route::prefix('v1/marketplace')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('/categories', function (CatalogService $catalog) {
            return response()->json([
                'success' => true,
                'data' => $catalog->listCategories(),
            ]);
        });

        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);

        Route::post('/orders', [OrderController::class, 'store']);
        Route::post('/orders/{id}/place', [OrderController::class, 'place']);
        Route::post('/orders/{id}/fulfill', [OrderController::class, 'fulfill']);
        Route::post('/orders/{id}/refund', [OrderController::class, 'refund']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);

        Route::post('/vendors', [VendorController::class, 'store']);
        Route::get('/vendors', [VendorController::class, 'index']);
        Route::get('/vendors/{id}', [VendorController::class, 'show']);
        Route::post('/vendors/{id}/approve', [VendorController::class, 'approve']);
        Route::post('/vendors/{id}/suspend', [VendorController::class, 'suspend']);

        Route::post('/gift-cards/purchase', [GiftCardController::class, 'purchaseGiftCards']);
        Route::post('/gift-cards/{id}/deliver', [GiftCardController::class, 'deliverGiftCard']);
        Route::post('/gift-cards/redeem', [GiftCardController::class, 'redeemGiftCard']);
        Route::get('/gift-cards/balance', [GiftCardController::class, 'checkBalance']);
        Route::get('/gift-cards/my-sent', [GiftCardController::class, 'listByVendor']);
        Route::get('/gift-cards/received', [GiftCardController::class, 'listByRecipient']);

        Route::post('/promo-codes', [PromoController::class, 'create']);
        Route::get('/promo-codes', [PromoController::class, 'listActive']);
        Route::post('/promo-codes/validate', [PromoController::class, 'validateCode']);
        Route::post('/promo-codes/apply', [PromoController::class, 'applyCode']);

        Route::get('/loyalty/balance', [LoyaltyController::class, 'getBalance']);
        Route::post('/loyalty/redeem', [LoyaltyController::class, 'redeemPoints']);
        Route::get('/loyalty/history', [LoyaltyController::class, 'getHistory']);
    });

Route::prefix('shipping')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::post('/calculate', [ShippingController::class, 'calculate']);
        Route::post('/create', [ShippingController::class, 'create']);
        Route::get('/track', [ShippingController::class, 'track']);
        Route::match(['get', 'post'], '/zones', [ShippingController::class, 'zones']);
        Route::get('/orders/{orderId}', [ShippingController::class, 'listByOrder']);
    });

Route::prefix('cod')
    ->middleware(['auth:api'])
    ->group(function () {
        Route::post('/collect', [CodController::class, 'collect']);
        Route::post('/remit/{id}', [CodController::class, 'remit']);
        Route::get('/pending', [CodController::class, 'pending']);
        Route::get('/agent', [CodController::class, 'agent']);
    });

Route::prefix('v1')
    ->middleware(['auth:api', 'throttle:60,1'])
    ->group(function () {
        Route::get('/products', [ApiController::class, 'products']);
        Route::get('/products/{id}', [ApiController::class, 'productDetail']);
        Route::get('/categories', [ApiController::class, 'categories']);
        Route::post('/orders', [ApiController::class, 'createOrder']);
        Route::get('/orders/{id}', [ApiController::class, 'orderStatus']);
        Route::post('/fulfillments/webhook', [ApiController::class, 'fulfillmentWebhook'])->withoutMiddleware('throttle:60,1');
    });
