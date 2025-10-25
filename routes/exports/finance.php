<?php

use App\Http\Controllers\Finance\BankTransferController;
use App\Http\Controllers\Finance\FeeConcessionExportController;
use App\Http\Controllers\Finance\FeeGroupExportController;
use App\Http\Controllers\Finance\FeeHeadExportController;
use App\Http\Controllers\Finance\FeeStructureExportController;
use App\Http\Controllers\Finance\LedgerExportController;
use App\Http\Controllers\Finance\LedgerTypeExportController;
use App\Http\Controllers\Finance\PaymentMethodExportController;
use App\Http\Controllers\Finance\Report\BankTransferExportController;
use App\Http\Controllers\Finance\Report\DayBookExportController;
use App\Http\Controllers\Finance\Report\DetailedFeePaymentExportController;
use App\Http\Controllers\Finance\Report\FeeConcessionExportController as ReportFeeConcessionExportController;
use App\Http\Controllers\Finance\Report\FeeDueExportController;
use App\Http\Controllers\Finance\Report\FeeHeadExportController as ReportFeeHeadExportController;
use App\Http\Controllers\Finance\Report\FeePaymentExportController;
use App\Http\Controllers\Finance\Report\FeeRefundExportController;
use App\Http\Controllers\Finance\Report\FeeSummaryExportController;
use App\Http\Controllers\Finance\Report\HeadWiseFeePaymentExportController;
use App\Http\Controllers\Finance\Report\InstallmentWiseFeeDueExportController;
use App\Http\Controllers\Finance\Report\OnlineFeePaymentExportController;
use App\Http\Controllers\Finance\Report\PaymentMethodWiseFeePaymentExportController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Finance\TransactionExportController;
use Illuminate\Support\Facades\Route;

Route::get('finance/payment-methods/export', PaymentMethodExportController::class)->middleware('permission:finance:config');

Route::get('finance/ledger-types/export', LedgerTypeExportController::class)->middleware('permission:ledger-type:export')->name('finance.ledger-types.export');

Route::get('finance/ledgers/export', LedgerExportController::class)->middleware('permission:ledger:export')->name('finance.ledgers.export');

Route::get('finance/transactions/{transaction}/media/{uuid}', [TransactionController::class, 'downloadMedia']);
Route::get('finance/bank-transfers/{bankTransfer}/media/{uuid}', [BankTransferController::class, 'downloadMedia']);

Route::get('finance/transactions/{transaction}/export', [TransactionController::class, 'export']);

Route::get('finance/transactions/export', TransactionExportController::class)->middleware('permission:transaction:export')->name('finance.transactions.export');

Route::get('finance/fee-groups/export', FeeGroupExportController::class)->middleware('permission:fee-group:export')->name('finance.fee-groups.export');

Route::get('finance/fee-heads/export', FeeHeadExportController::class)->middleware('permission:fee-head:export')->name('finance.fee-heads.export');

Route::get('finance/fee-concessions/export', FeeConcessionExportController::class)->middleware('permission:fee-concession:export')->name('finance.fee-concessions.export');

Route::get('finance/fee-structures/export', FeeStructureExportController::class)->middleware('permission:fee-structure:export')->name('finance.fee-structures.export');

Route::get('finance/reports/day-book/export', DayBookExportController::class)->middleware('permission:finance:report')->name('finance.reports.day-book.export');

Route::get('finance/reports/fee-summary/export', FeeSummaryExportController::class)->middleware('permission:finance:report')->name('finance.reports.fee-summary.export');

Route::get('finance/reports/fee-concession/export', ReportFeeConcessionExportController::class)->middleware('permission:finance:report')->name('finance.reports.fee-concession.export');

Route::get('finance/reports/installment-wise-fee-due/export', InstallmentWiseFeeDueExportController::class)->middleware('permission:finance:report')->name('finance.reports.installment-wise-fee-due.export');

Route::get('finance/reports/fee-due/export', FeeDueExportController::class)->middleware('permission:finance:report')->name('finance.reports.fee-due.export');

Route::get('finance/reports/fee-head/export', ReportFeeHeadExportController::class)->middleware('permission:finance:report')->name('finance.reports.fee-head.export');

Route::get('finance/reports/detailed-fee-payment/export', DetailedFeePaymentExportController::class)->middleware('permission:finance:report')->name('finance.reports.detailed-fee-payment.export');

Route::get('finance/reports/fee-payment/export', FeePaymentExportController::class)->middleware('permission:finance:report')->name('finance.reports.fee-payment.export');

Route::get('finance/reports/online-fee-payment/export', OnlineFeePaymentExportController::class)->middleware('permission:finance:report')->name('finance.reports.online-fee-payment.export');

Route::get('finance/reports/head-wise-fee-payment/export', HeadWiseFeePaymentExportController::class)->middleware('permission:finance:report')->name('finance.reports.head-wise-fee-payment.export');

Route::get('finance/reports/payment-method-wise-fee-payment/export', PaymentMethodWiseFeePaymentExportController::class)->middleware('permission:finance:report')->name('finance.reports.payment-method-wise-fee-payment.export');

Route::get('finance/reports/bank-transfer/export', BankTransferExportController::class)->middleware('permission:finance:report')->name('finance.reports.bank-transfer.export');

Route::get('finance/reports/fee-refund/export', FeeRefundExportController::class)->middleware('permission:finance:report')->name('finance.reports.fee-refund.export');
