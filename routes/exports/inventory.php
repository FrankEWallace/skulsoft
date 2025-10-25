<?php

use App\Http\Controllers\Inventory\InchargeExportController;
use App\Http\Controllers\Inventory\InventoryExportController;
use App\Http\Controllers\Inventory\Report\ItemSummaryExportController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockAdjustmentExportController;
use App\Http\Controllers\Inventory\StockCategoryExportController;
use App\Http\Controllers\Inventory\StockItemExportController;
use App\Http\Controllers\Inventory\StockPurchaseController;
use App\Http\Controllers\Inventory\StockPurchaseExportController;
use App\Http\Controllers\Inventory\StockRequisitionController;
use App\Http\Controllers\Inventory\StockRequisitionExportController;
use App\Http\Controllers\Inventory\StockReturnController;
use App\Http\Controllers\Inventory\StockReturnExportController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\StockTransferExportController;
use App\Http\Controllers\Inventory\VendorExportController;
use App\Http\Controllers\Inventory\VendorStatementExportController;
use Illuminate\Support\Facades\Route;

Route::get('inventories/export', InventoryExportController::class)->middleware('permission:inventory:config');

Route::get('inventory/incharges/export', InchargeExportController::class)->middleware('permission:inventory:config')->name('inventory.incharges.export');

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('vendors/export', VendorExportController::class)->middleware('permission:vendor:export');

    Route::get('vendor/statement/export', VendorStatementExportController::class)->middleware('permission:vendor:export');

    Route::get('stock-categories/export', StockCategoryExportController::class)->middleware('permission:stock-category:export');

    Route::get('stock-items/export', StockItemExportController::class)->middleware('permission:stock-item:export');

    Route::get('stock-requisitions/{stock_requisition}/media/{uuid}', [StockRequisitionController::class, 'downloadMedia']);
    Route::get('stock-requisitions/{stock_requisition}/export', [StockRequisitionController::class, 'export']);
    Route::get('stock-requisitions/export', StockRequisitionExportController::class)->middleware('permission:stock-requisition:export');

    Route::get('stock-purchases/{stock_purchase}/media/{uuid}', [StockPurchaseController::class, 'downloadMedia']);
    Route::get('stock-purchases/export', StockPurchaseExportController::class)->middleware('permission:stock-purchase:export');

    Route::get('stock-returns/{stock_return}/media/{uuid}', [StockReturnController::class, 'downloadMedia']);
    Route::get('stock-returns/export', StockReturnExportController::class)->middleware('permission:stock-return:export');

    Route::get('stock-transfers/{stock_transfer}/media/{uuid}', [StockTransferController::class, 'downloadMedia']);
    Route::get('stock-transfers/export', StockTransferExportController::class)->middleware('permission:stock-transfer:export');

    Route::get('stock-adjustments/{stock_adjustment}/media/{uuid}', [StockAdjustmentController::class, 'downloadMedia']);
    Route::get('stock-adjustments/export', StockAdjustmentExportController::class)->middleware('permission:stock-adjustment:export');

    Route::get('reports/item-summary/export', ItemSummaryExportController::class)->middleware('permission:inventory:report')->name('reports.item-summary.export');
});
