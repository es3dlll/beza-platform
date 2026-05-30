<?php

use Illuminate\Support\Facades\Route;
use Modules\Education\Controllers\EducationController;

Route::middleware(['auth:api'])->prefix('v1/education')->group(function () {
    Route::get('institutions', [EducationController::class, 'institutions']);
    Route::post('register-student', [EducationController::class, 'registerStudent']);
    Route::post('create-fee', [EducationController::class, 'createFee']);
    Route::post('{id}/pay-fee', [EducationController::class, 'payFee']);
    Route::get('student/{id}/fees', [EducationController::class, 'studentFees']);
    Route::get('{id}/dashboard', [EducationController::class, 'institutionDashboard']);
    Route::post('bulk-fees', [EducationController::class, 'bulkCreateFees']);
    Route::get('{id}/overdue', [EducationController::class, 'overdueFees']);
    Route::get('receipt/{feeId}', [EducationController::class, 'receipt']);
});
