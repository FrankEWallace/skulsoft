<?php

use App\Http\Controllers\Communication\AnnouncementController;
use App\Http\Controllers\Communication\AnnouncementExportController;
use App\Http\Controllers\Communication\EmailController;
use App\Http\Controllers\Communication\EmailExportController;
use App\Http\Controllers\Communication\SMSExportController;
use App\Http\Controllers\Communication\WhatsAppExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('communication')->name('communication.')->group(function () {
    Route::get('announcements/{announcement}/media/{uuid}', [AnnouncementController::class, 'downloadMedia']);
    Route::get('announcements/export', AnnouncementExportController::class)->middleware('permission:announcement:export');

    Route::get('emails/{email}/media/{uuid}', [EmailController::class, 'downloadMedia']);
    Route::get('emails/export', EmailExportController::class)->middleware('permission:email:read');

    Route::get('sms/export', SMSExportController::class)->middleware('permission:sms:read');

    Route::get('whatsapp/export', WhatsAppExportController::class)->middleware('permission:whatsapp:read');
});
