<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\USSD\Controllers\UssdController;

Route::prefix('v1/ussd')->group(function () {
    Route::post('/handle', [UssdController::class, 'handle']);
    Route::post('/callback', [UssdController::class, 'callback']);
});
