<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Controllers\EducationAdminController;
use Modules\Admin\Controllers\EscrowAdminController;
use Modules\Admin\Controllers\FinancingAdminController;
use Modules\Admin\Controllers\HumanitarianAdminController;
use Modules\Admin\Controllers\InvestmentsAdminController;
use Modules\Admin\Controllers\MarketplaceAdminController;
use Modules\Admin\Controllers\OpenFinanceAdminController;
use Modules\Admin\Controllers\TakafulAdminController;

Route::middleware(['auth:api', 'permission:admin.access'])->prefix('v1/admin')->group(function () {

    Route::prefix('financing')->group(function () {
        Route::get('dashboard', [FinancingAdminController::class, 'dashboard']);
        Route::get('pending-approvals', [FinancingAdminController::class, 'pendingApprovals']);
        Route::post('loans/{id}/approve', [FinancingAdminController::class, 'approveLoan']);
        Route::post('loans/{id}/reject', [FinancingAdminController::class, 'rejectLoan']);
        Route::get('loans/{id}', [FinancingAdminController::class, 'loanDetail']);
        Route::post('loans/{id}/write-off', [FinancingAdminController::class, 'writeOff']);
        Route::get('loans', [FinancingAdminController::class, 'listLoans']);
        Route::get('products', [FinancingAdminController::class, 'productConfig']);
        Route::put('products/{id}', [FinancingAdminController::class, 'updateProductConfig']);
    });

    Route::prefix('education')->group(function () {
        Route::get('dashboard', [EducationAdminController::class, 'dashboard']);
        Route::get('institutions', [EducationAdminController::class, 'institutions']);
        Route::get('institutions/{id}', [EducationAdminController::class, 'institutionDetail']);
        Route::post('institutions', [EducationAdminController::class, 'createInstitution']);
        Route::put('institutions/{id}', [EducationAdminController::class, 'updateInstitution']);
        Route::get('overdue-students', [EducationAdminController::class, 'listOverdueStudents']);
        Route::get('collection-report', [EducationAdminController::class, 'collectionReport']);
    });

    Route::prefix('humanitarian')->group(function () {
        Route::get('dashboard', [HumanitarianAdminController::class, 'dashboard']);
        Route::get('programs', [HumanitarianAdminController::class, 'listPrograms']);
        Route::get('programs/{id}', [HumanitarianAdminController::class, 'programDetail']);
        Route::post('programs/{id}/approve', [HumanitarianAdminController::class, 'approveProgram']);
        Route::post('programs/{id}/suspend', [HumanitarianAdminController::class, 'suspendProgram']);
        Route::get('budget-alerts', [HumanitarianAdminController::class, 'budgetAlerts']);
        Route::get('donor-report/{programId}', [HumanitarianAdminController::class, 'donorReport']);
    });

    Route::prefix('open-finance')->group(function () {
        Route::get('dashboard', [OpenFinanceAdminController::class, 'dashboard']);
        Route::get('apps', [OpenFinanceAdminController::class, 'listApps']);
        Route::get('apps/{id}', [OpenFinanceAdminController::class, 'appDetail']);
        Route::post('apps/{id}/revoke', [OpenFinanceAdminController::class, 'revokeApp']);
        Route::post('keys/{id}/suspend', [OpenFinanceAdminController::class, 'suspendKey']);
        Route::get('usage/{appId}', [OpenFinanceAdminController::class, 'usageMetrics']);
        Route::get('webhook-logs', [OpenFinanceAdminController::class, 'webhookLogs']);
    });

    Route::prefix('marketplace')->group(function () {
        Route::get('dashboard', [MarketplaceAdminController::class, 'dashboard']);
        Route::get('vendors', [MarketplaceAdminController::class, 'listVendors']);
        Route::get('vendors/{id}', [MarketplaceAdminController::class, 'vendorDetail']);
        Route::post('vendors/{id}/approve', [MarketplaceAdminController::class, 'approveVendor']);
        Route::post('vendors/{id}/suspend', [MarketplaceAdminController::class, 'suspendVendor']);
        Route::post('products/{id}/moderate', [MarketplaceAdminController::class, 'moderateProduct']);
        Route::get('orders', [MarketplaceAdminController::class, 'listOrders']);
        Route::get('orders/{id}', [MarketplaceAdminController::class, 'orderDetail']);
        Route::get('commissions', [MarketplaceAdminController::class, 'commissionReport']);
        Route::get('settlements', [MarketplaceAdminController::class, 'settlementReport']);
    });

    Route::prefix('escrow')->group(function () {
        Route::get('dashboard', [EscrowAdminController::class, 'dashboard']);
        Route::get('disputes', [EscrowAdminController::class, 'disputeQueue']);
        Route::get('disputes/{id}', [EscrowAdminController::class, 'disputeDetail']);
        Route::post('disputes/{id}/resolve', [EscrowAdminController::class, 'resolveDispute']);
        Route::get('agreements', [EscrowAdminController::class, 'listAgreements']);
        Route::get('agreements/{id}', [EscrowAdminController::class, 'agreementDetail']);
    });

    Route::prefix('takaful')->group(function () {
        Route::get('dashboard', [TakafulAdminController::class, 'dashboard']);
        Route::get('policies', [TakafulAdminController::class, 'listPolicies']);
        Route::get('policies/{id}', [TakafulAdminController::class, 'policyDetail']);
        Route::get('claims', [TakafulAdminController::class, 'listClaims']);
        Route::get('claims/{id}', [TakafulAdminController::class, 'claimDetail']);
        Route::post('claims/{id}/approve', [TakafulAdminController::class, 'approveClaim']);
        Route::post('claims/{id}/reject', [TakafulAdminController::class, 'rejectClaim']);
    });

    Route::prefix('investments')->group(function () {
        Route::get('dashboard', [InvestmentsAdminController::class, 'dashboard']);
        Route::get('funds', [InvestmentsAdminController::class, 'listFunds']);
        Route::get('funds/{id}', [InvestmentsAdminController::class, 'fundDetail']);
        Route::post('nav', [InvestmentsAdminController::class, 'recordNav']);
        Route::get('subscriptions', [InvestmentsAdminController::class, 'subscriptionQueue']);
        Route::post('subscriptions/{id}/settle', [InvestmentsAdminController::class, 'settleSubscription']);
        Route::get('reconcile/{fundId}', [InvestmentsAdminController::class, 'reconcileReport']);
    });
});
