<?php

use App\Http\Controllers\Finance\FeeConcessionController;
use App\Http\Controllers\Finance\FeeGroupController;
use App\Http\Controllers\Finance\FeeHeadController;
use App\Http\Controllers\Finance\FeeInstallmentController;
use App\Http\Controllers\Finance\FeeStructureActionController;
use App\Http\Controllers\Finance\FeeStructureController;
use App\Http\Controllers\Finance\LedgerController;
use App\Http\Controllers\Finance\LedgerTypeController;
use App\Http\Controllers\Finance\PaymentMethodController;
use App\Http\Controllers\Finance\Report\BankTransferController;
use App\Http\Controllers\Finance\Report\DayBookController;
use App\Http\Controllers\Finance\Report\FeeConcessionController as ReportFeeConcessionController;
use App\Http\Controllers\Finance\Report\FeeDueController;
use App\Http\Controllers\Finance\Report\FeeHeadController as ReportFeeHeadController;
use App\Http\Controllers\Finance\Report\FeePaymentController;
use App\Http\Controllers\Finance\Report\FeeRefundController;
use App\Http\Controllers\Finance\Report\FeeSummaryController;
use App\Http\Controllers\Finance\Report\HeadWiseFeePaymentController;
use App\Http\Controllers\Finance\Report\InstallmentWiseFeeDueController;
use App\Http\Controllers\Finance\Report\OnlineFeePaymentController;
use App\Http\Controllers\Finance\Report\PaymentMethodWiseFeePaymentController;
use App\Http\Controllers\Finance\TransactionActionController;
use App\Http\Controllers\Finance\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->name('finance.')->group(function () {
    Route::middleware('permission:finance:config')->group(function () {
        Route::get('payment-methods/pre-requisite', [PaymentMethodController::class, 'preRequisite']);
        Route::apiResource('payment-methods', PaymentMethodController::class);
    });

    Route::get('ledger-types/pre-requisite', [LedgerTypeController::class, 'preRequisite'])->name('ledger-types.preRequisite');

    Route::apiResource('ledger-types', LedgerTypeController::class);

    Route::get('ledgers/pre-requisite', [LedgerController::class, 'preRequisite'])->name('ledgers.preRequisite');

    Route::apiResource('ledgers', LedgerController::class);

    Route::get('transactions/pre-requisite', [TransactionController::class, 'preRequisite'])->name('transactions.preRequisite');

    Route::post('transactions/{transaction}/clearing-date', [TransactionActionController::class, 'updateClearingDate'])->name('transactions.updateClearingDate');

    Route::apiResource('transactions', TransactionController::class);

    Route::get('fee-groups/pre-requisite', [FeeGroupController::class, 'preRequisite'])->name('fee-groups.preRequisite');

    Route::apiResource('fee-groups', FeeGroupController::class);

    Route::get('fee-heads/pre-requisite', [FeeHeadController::class, 'preRequisite'])->name('fee-heads.preRequisite');

    Route::apiResource('fee-heads', FeeHeadController::class);

    Route::get('fee-concessions/pre-requisite', [FeeConcessionController::class, 'preRequisite'])->name('fee-concessions.preRequisite');

    Route::apiResource('fee-concessions', FeeConcessionController::class);

    Route::get('fee-structures/pre-requisite', [FeeStructureController::class, 'preRequisite'])->name('fee-structures.preRequisite');

    Route::post('fee-structures/{fee_structure}/allocation', [FeeStructureActionController::class, 'allocation']);
    Route::delete('fee-structures/{fee_structure}/allocations/{allocation}', [FeeStructureActionController::class, 'removeAllocation']);

    Route::post('fee-structures/{fee_structure}/installments', [FeeInstallmentController::class, 'create']);
    Route::get('fee-structures/{fee_structure}/installments/{uuid}', [FeeInstallmentController::class, 'show']);
    Route::patch('fee-structures/{fee_structure}/installments/{uuid}', [FeeInstallmentController::class, 'update']);
    Route::delete('fee-structures/{fee_structure}/installments/{uuid}', [FeeInstallmentController::class, 'destroy']);

    Route::apiResource('fee-structures', FeeStructureController::class);

    Route::prefix('reports')->name('reports.')->middleware('permission:finance:report')->group(function () {
        Route::get('day-book/pre-requisite', [DayBookController::class, 'preRequisite'])->name('day-book.preRequisite');
        Route::get('day-book', [DayBookController::class, 'fetch'])->name('day-book.fetch');

        Route::get('fee-summary/pre-requisite', [FeeSummaryController::class, 'preRequisite'])->name('fee-summary.preRequisite');
        Route::get('fee-summary', [FeeSummaryController::class, 'fetch'])->name('fee-summary.fetch');

        Route::get('fee-concession/pre-requisite', [ReportFeeConcessionController::class, 'preRequisite'])->name('fee-concession.preRequisite');
        Route::get('fee-concession', [ReportFeeConcessionController::class, 'fetch'])->name('fee-concession.fetch');

        Route::get('installment-wise-fee-due/pre-requisite', [InstallmentWiseFeeDueController::class, 'preRequisite'])->name('installment-wise-fee-due.preRequisite');
        Route::get('installment-wise-fee-due', [InstallmentWiseFeeDueController::class, 'fetch'])->name('installment-wise-fee-due.fetch');

        Route::get('fee-due/pre-requisite', [FeeDueController::class, 'preRequisite'])->name('fee-due.preRequisite');
        Route::get('fee-due', [FeeDueController::class, 'fetch'])->name('fee-due.fetch');

        Route::get('fee-head/pre-requisite', [ReportFeeHeadController::class, 'preRequisite'])->name('fee-head.preRequisite');
        Route::get('fee-head', [ReportFeeHeadController::class, 'fetch'])->name('fee-head.fetch');

        Route::get('fee-payment/pre-requisite', [FeePaymentController::class, 'preRequisite'])->name('fee-payment.preRequisite');
        Route::get('fee-payment', [FeePaymentController::class, 'fetch'])->name('fee-payment.fetch');

        Route::get('online-fee-payment/pre-requisite', [OnlineFeePaymentController::class, 'preRequisite'])->name('online-fee-payment.preRequisite');
        Route::get('online-fee-payment', [OnlineFeePaymentController::class, 'fetch'])->name('online-fee-payment.fetch');

        Route::get('head-wise-fee-payment/pre-requisite', [HeadWiseFeePaymentController::class, 'preRequisite'])->name('head-wise-fee-payment.preRequisite');
        Route::get('head-wise-fee-payment', [HeadWiseFeePaymentController::class, 'fetch'])->name('head-wise-fee-payment.fetch');

        Route::get('bank-transfer/pre-requisite', [BankTransferController::class, 'preRequisite'])->name('bank-transfer.preRequisite');
        Route::get('bank-transfer', [BankTransferController::class, 'fetch'])->name('bank-transfer.fetch');

        Route::get('payment-method-wise-fee-payment/pre-requisite', [PaymentMethodWiseFeePaymentController::class, 'preRequisite'])->name('payment-method-wise-fee-payment.preRequisite');
        Route::get('payment-method-wise-fee-payment', [PaymentMethodWiseFeePaymentController::class, 'fetch'])->name('payment-method-wise-fee-payment.fetch');

        Route::get('fee-refund/pre-requisite', [FeeRefundController::class, 'preRequisite'])->name('fee-refund.preRequisite');
        Route::get('fee-refund', [FeeRefundController::class, 'fetch'])->name('fee-refund.fetch');
    });
});
