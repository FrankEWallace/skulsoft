<?php

use App\Http\Controllers\Reception\CallLogController;
use App\Http\Controllers\Reception\CallLogExportController;
use App\Http\Controllers\Reception\ComplaintController;
use App\Http\Controllers\Reception\ComplaintExportController;
use App\Http\Controllers\Reception\CorrespondenceController;
use App\Http\Controllers\Reception\CorrespondenceExportController;
use App\Http\Controllers\Reception\EnquiryController;
use App\Http\Controllers\Reception\EnquiryExportController;
use App\Http\Controllers\Reception\EnquiryQualificationController;
use App\Http\Controllers\Reception\GatePassController;
use App\Http\Controllers\Reception\GatePassExportController;
use App\Http\Controllers\Reception\QueryExportController;
use App\Http\Controllers\Reception\VisitorLogController;
use App\Http\Controllers\Reception\VisitorLogExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reception')->name('reception.')->group(function () {
    Route::get('enquiries/{enquiry}/qualifications/{qualification}/media/{uuid}', [EnquiryQualificationController::class, 'downloadMedia']);

    Route::get('enquiries/{enquiry}/media/{uuid}', [EnquiryController::class, 'downloadMedia']);
    Route::get('enquiries/{enquiry}/export', [EnquiryController::class, 'export']);
    Route::get('enquiries/export', EnquiryExportController::class)->middleware('permission:enquiry:export');

    Route::get('visitor-logs/{visitor_log}/media/{uuid}', [VisitorLogController::class, 'downloadMedia']);
    Route::get('visitor-logs/{visitor_log}/export', [VisitorLogController::class, 'export']);
    Route::get('visitor-logs/export', VisitorLogExportController::class)->middleware('permission:visitor-log:export');

    Route::get('gate-passes/{gate_pass}/media/{uuid}', [GatePassController::class, 'downloadMedia']);
    Route::get('gate-passes/{gate_pass}/export', [GatePassController::class, 'export']);
    Route::get('gate-passes/export', GatePassExportController::class)->middleware('permission:gate-pass:export');

    Route::get('complaints/{complaint}/media/{uuid}', [ComplaintController::class, 'downloadMedia']);
    Route::get('complaints/export', ComplaintExportController::class)->middleware('permission:complaint:export');

    Route::get('call-logs/{call_log}/media/{uuid}', [CallLogController::class, 'downloadMedia']);
    Route::get('call-logs/export', CallLogExportController::class)->middleware('permission:call-log:export');

    Route::get('correspondences/{correspondence}/media/{uuid}', [CorrespondenceController::class, 'downloadMedia']);
    Route::get('correspondences/export', CorrespondenceExportController::class)->middleware('permission:correspondence:export');

    Route::get('queries/export', QueryExportController::class)->middleware('permission:query:read');
});
