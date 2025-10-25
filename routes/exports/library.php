<?php

use App\Http\Controllers\Library\BookAdditionExportController;
use App\Http\Controllers\Library\BookCopyExportController;
use App\Http\Controllers\Library\BookExportController;
use App\Http\Controllers\Library\BookWiseTransactionExportController;
use App\Http\Controllers\Library\Report\TopBorrowedBookExportController;
use App\Http\Controllers\Library\Report\TopBorrowerExportController;
use App\Http\Controllers\Library\TransactionExportController;
use Illuminate\Support\Facades\Route;

Route::get('library/books/export', BookExportController::class)->middleware('permission:book:export');

Route::get('library/book-copies/export', BookCopyExportController::class)->middleware('permission:book-copy:export');

Route::get('library/book-additions/export', BookAdditionExportController::class)->middleware('permission:book-addition:export');

Route::get('library/book-wise-transactions/export', BookWiseTransactionExportController::class)->middleware('permission:book:issue');

Route::get('library/transactions/export', TransactionExportController::class)->middleware('permission:book:issue');

Route::get('library/reports/top-borrower/export', TopBorrowerExportController::class)->middleware('permission:library:report')->name('reports.top-borrower.export');

Route::get('library/reports/top-borrowed-book/export', TopBorrowedBookExportController::class)->middleware('permission:library:report')->name('reports.top-borrowed-book.export');
