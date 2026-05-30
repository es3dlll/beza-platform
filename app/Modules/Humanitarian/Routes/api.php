<?php

use Illuminate\Support\Facades\Route;
use Modules\Humanitarian\Controllers\HumanitarianController;

Route::middleware(['auth:api'])->prefix('v1/humanitarian')->group(function () {
    Route::get('organizations', [HumanitarianController::class, 'organizations']);
    Route::get('programs', [HumanitarianController::class, 'programs']);
    Route::post('disburse', [HumanitarianController::class, 'disburse']);
    Route::get('history', [HumanitarianController::class, 'history']);
});
