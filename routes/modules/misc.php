<?php

use App\Http\Controllers\Library\BookController;
use App\Http\Controllers\Misc\InstituteController;
use App\Http\Controllers\Misc\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('suggestions/institutes', [InstituteController::class, 'searchInstitute'])->name('suggestions.institutes');
Route::get('suggestions/affiliation-bodies', [InstituteController::class, 'searchAffiliationBody'])->name('suggestions.affiliation-bodies');

Route::get('suggestions/units', [UnitController::class, 'searchUnit'])->name('suggestions.units');

Route::get('suggestions/library/books', [BookController::class, 'searchBook'])->name('suggestions.library.books');
