<?php

declare(strict_types=1);

use App\Modules\Ledger\Http\Controllers\CBSReportController;
use App\Modules\Ledger\Http\Controllers\ExecutiveDashboardController;
use App\Modules\Ledger\Http\Controllers\FeedbackController;
use App\Modules\Ledger\Http\Controllers\ReconciliationController;
use App\Modules\Ledger\Models\JournalEntry;
use App\Modules\Ledger\Services\AccountService;
use App\Modules\Ledger\Services\JournalService;
use App\Modules\Ledger\Services\LedgerHealthCheck;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/ledger')->group(function () {
    Route::get('/accounts', function (AccountService $service) {
        $type = request()->query('type');
        return response()->json($service->listAccounts($type));
    });

    Route::get('/accounts/{id}', function (string $id, AccountService $service) {
        return response()->json($service->getAccount($id));
    });

    Route::get('/journal', function (JournalService $service) {
        $perPage = (int) request()->query('per_page', 20);
        $entries = JournalEntry::with('lines.account')
            ->orderBy('created_at', 'desc')
            ->paginate(min($perPage, 100));
        return response()->json($entries);
    });

    Route::get('/journal/{id}', function (string $id, JournalService $service) {
        return response()->json($service->getEntry($id));
    });

    Route::get('/trial-balance', function (JournalService $service) {
        $currency = request()->query('currency');
        return response()->json($service->getTrialBalance($currency));
    });

    Route::get('/verify-chain', function (JournalService $service) {
        return response()->json($service->verifyChain());
    });

    Route::middleware(['auth', 'throttle:30,1'])->group(function () {
        Route::get('/health', function (LedgerHealthCheck $healthCheck) {
            $result = $healthCheck->check();
            $statusCode = $result['status'] === 'degraded' ? 503 : 200;
            return response()->json($result, $statusCode);
        });
    });

    Route::middleware(['auth', 'throttle:10,1'])->group(function () {
        Route::get('/reconciliations', [ReconciliationController::class, 'index']);
        Route::post('/reconciliations/run', [ReconciliationController::class, 'run']);
        Route::get('/cbs-reports/{id}', [CBSReportController::class, 'show']);
    });

    Route::middleware(['auth', 'throttle:60,1'])->prefix('v1/executive')->group(function () {
        Route::get('/dashboard/summary', [ExecutiveDashboardController::class, 'summary']);
    });
});

Route::middleware('throttle:20,1')->prefix('v1/feedback')->group(function () {
    Route::post('/ledger', [FeedbackController::class, 'store']);
});
