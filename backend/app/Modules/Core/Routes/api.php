<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1/core')->group(function (): void {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Core module is running',
            'data' => null,
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-Id'),
        ]);
    });
});
