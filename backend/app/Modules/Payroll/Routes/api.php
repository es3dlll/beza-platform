<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Payroll\Controllers\PayrollController;

Route::middleware(['auth:api'])->prefix('v1/payroll')->group(function () {
    Route::post('register', [PayrollController::class, 'register']);
    Route::get('my', [PayrollController::class, 'myEmployer']);
    Route::post('{id}/approve', [PayrollController::class, 'approve']);
    Route::post('{id}/suspend', [PayrollController::class, 'suspend']);

    Route::post('batches', [PayrollController::class, 'createBatch']);
    Route::post('batches/csv', [PayrollController::class, 'uploadCsv']);
    Route::get('batches', [PayrollController::class, 'listBatches']);
    Route::get('batches/{id}', [PayrollController::class, 'showBatch']);
    Route::post('batches/{id}/approve', [PayrollController::class, 'approveBatch']);
    Route::post('batches/{id}/process', [PayrollController::class, 'processBatch']);

    Route::get('{employerId}/employees', [PayrollController::class, 'listEmployees']);

    Route::get('dashboard', [PayrollController::class, 'dashboard']);
    Route::get('my-salary', [PayrollController::class, 'mySalary']);
    Route::get('certificate/{batchId}/{employeePhone}', [PayrollController::class, 'downloadCertificate']);
});
