<?php

use App\Http\Controllers\Library\BookAdditionController;
use App\Http\Controllers\Library\BookController;
use App\Http\Controllers\Library\BookCopyActionController;
use App\Http\Controllers\Library\BookCopyController;
use App\Http\Controllers\Library\BookCopyImportController;
use App\Http\Controllers\Library\BookImportController;
use App\Http\Controllers\Library\BookLabelController;
use App\Http\Controllers\Library\BookWiseTransactionController;
use App\Http\Controllers\Library\Report\TopBorrowedBookController;
use App\Http\Controllers\Library\Report\TopBorrowerController;
use App\Http\Controllers\Library\TransactionActionController;
use App\Http\Controllers\Library\TransactionController;
use Illuminate\Support\Facades\Route;

// Library Routes
Route::prefix('library')->middleware('permission:library:config')->group(function () {});

Route::prefix('library')->name('library.')->group(function () {
    Route::get('books/pre-requisite', [BookController::class, 'preRequisite']);
    Route::post('books/import', BookImportController::class)->middleware('permission:book:create');
    Route::apiResource('books', BookController::class);

    Route::get('book-copies/pre-requisite', [BookCopyController::class, 'preRequisite']);
    Route::post('book-copies/condition', [BookCopyActionController::class, 'updateBulkCondition'])->middleware('permission:book:edit');
    Route::post('book-copies/status', [BookCopyActionController::class, 'updateBulkStatus'])->middleware('permission:book:edit');
    Route::post('book-copies/location', [BookCopyActionController::class, 'updateBulkLocation'])->middleware('permission:book:edit');
    Route::post('book-copies/import', BookCopyImportController::class)->middleware('permission:book-addition:create');
    Route::apiResource('book-copies', BookCopyController::class)->only(['index']);

    Route::get('book/labels/pre-requisite', [BookLabelController::class, 'preRequisite'])->name('bookLabels.preRequisite');
    Route::get('book/labels', [BookLabelController::class, 'print'])->name('bookLabels.print');

    Route::get('book-additions/pre-requisite', [BookAdditionController::class, 'preRequisite']);
    Route::apiResource('book-additions', BookAdditionController::class);

    Route::get('book-wise-transactions/pre-requisite', [BookWiseTransactionController::class, 'preRequisite']);
    Route::apiResource('book-wise-transactions', BookWiseTransactionController::class);

    Route::get('transactions/pre-requisite', [TransactionController::class, 'preRequisite']);
    Route::get('transactions/action-pre-requisite', [TransactionController::class, 'actionPreRequisite']);
    Route::post('transactions/{book_issue}/return', [TransactionActionController::class, 'returnBook'])->name('transactions.returnBook');
    Route::apiResource('transactions', TransactionController::class);

    Route::prefix('reports')->name('reports.')->middleware('permission:library:report')->group(function () {
        Route::get('top-borrower/pre-requisite', [TopBorrowerController::class, 'preRequisite'])->name('top-borrower.preRequisite');
        Route::get('top-borrower', [TopBorrowerController::class, 'fetch'])->name('top-borrower.fetch');

        Route::get('top-borrowed-book/pre-requisite', [TopBorrowedBookController::class, 'preRequisite'])->name('top-borrowed-book.preRequisite');
        Route::get('top-borrowed-book', [TopBorrowedBookController::class, 'fetch'])->name('top-borrowed-book.fetch');
    });
});
