<?php

use App\Http\Controllers\Custom\FeeMismatchController;
use App\Http\Controllers\Custom\FeePaymentConcessionSetController;
use App\Http\Controllers\Custom\HeadWiseFeeSummaryController;
use App\Http\Controllers\Custom\TransactionUserTransferController;
use App\Http\Controllers\Student\PromotionController;
use App\Services\Employee\Payroll\SalaryTemplateActionService;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:sanctum', 'user.config', 'role:admin'])->group(function () {
    Route::get('fee-mismatch', FeeMismatchController::class);

    Route::get('head-wise-fee-summary', HeadWiseFeeSummaryController::class);

    Route::get('cancel-promotion', [PromotionController::class, 'cancel']);

    Route::get('fee-payment-concession-set', FeePaymentConcessionSetController::class);

    Route::get('transaction-user-transfer', TransactionUserTransferController::class);

    Route::get('employee/payroll/salary-templates/{salary_template}/recalculate', [SalaryTemplateActionService::class, 'recalculate'])
        ->name('employee.payroll.salary-templates.recalculate');
});
