<?php

use Illuminate\Support\Facades\Route;
use Modules\GovCollections\Controllers\GovCollectionController;

Route::middleware(['auth:api'])->prefix('v1/gov-collections')->group(function () {
    Route::get('providers', [GovCollectionController::class, 'providers']);
    Route::post('inquire', [GovCollectionController::class, 'inquire']);
    Route::post('{id}/pay', [GovCollectionController::class, 'pay']);
    Route::get('history', [GovCollectionController::class, 'history']);
    Route::get('admin/summary', [GovCollectionController::class, 'adminSummary']);
});
